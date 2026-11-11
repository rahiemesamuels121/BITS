<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:200'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $clients = User::query()->whereHas('role', fn ($query) => $query->where('code', Role::AGENCY));
        if (! empty($filters['q'])) {
            $clients->where(fn ($query) => $query->where('name', 'like', '%'.$filters['q'].'%')->orWhere('email', 'like', '%'.$filters['q'].'%'));
        }
        if (! empty($filters['status'])) {
            $clients->where('is_active', $filters['status'] === 'active');
        }

        return view('clients.index', ['clients' => $clients->latest()->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return view('clients.create', [
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(12)],
            'is_active' => ['required', 'boolean'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ]);
        DB::transaction(function () use ($data) {
            $role = Role::query()->findOrFail($data['role_id']);
            $client = new User(collect($data)->only(['name', 'email', 'password'])->all());
            $client->role()->associate($role);
            $client->is_active = (bool) $data['is_active'];
            $client->save();
        });

        return to_route('clients.index')->with('status', 'Client account created.');
    }

    public function updateStatus(Request $request, User $client)
    {
        abort_unless($client->role?->code === Role::AGENCY, 404);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        DB::transaction(function () use ($client, $data) {
            $client = User::query()->lockForUpdate()->findOrFail($client->id);
            abort_unless($client->role?->code === Role::AGENCY, 404);
            $client->is_active = (bool) $data['is_active'];
            if (! $client->is_active) {
                $this->revokeAccess($client);
            }
            $client->save();
        });

        return to_route('clients.index')->with('status', $data['is_active'] ? 'Client account activated.' : 'Client account deactivated.');
    }

    public function edit(User $client)
    {
        abort_unless($client->role?->code === Role::AGENCY, 404);

        return view('clients.edit', [
            'client' => $client,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $client)
    {
        abort_unless($client->role?->code === Role::AGENCY, 404);
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:254', Rule::unique('users', 'email')->ignore($client->id)],
            'password' => ['nullable', 'string', 'max:72', 'confirmed', Password::min(12)],
            'role_id' => ['sometimes', 'required', 'integer', Rule::exists('roles', 'id')],
        ]);
        DB::transaction(function () use ($client, $data) {
            $client = User::query()->lockForUpdate()->findOrFail($client->id);
            abort_unless($client->role?->code === Role::AGENCY, 404);
            $oldEmail = $client->email;
            $client->fill(collect($data)->only(['name', 'email'])->all());
            if (isset($data['role_id'])) {
                $client->role()->associate(Role::query()->findOrFail($data['role_id']));
            }
            if (! empty($data['password'])) {
                $client->password = $data['password'];
            }
            if ($client->isDirty('email')) {
                $client->email_verified_at = null;
            }
            if ($client->isDirty(['email', 'password', 'role_id'])) {
                $this->revokeAccess($client);
                DB::table('password_reset_tokens')->where('email', $oldEmail)->delete();
            }
            $client->save();
        });

        return to_route('clients.index')->with('status', 'Client account updated.');
    }

    public function destroy(User $client)
    {
        abort_unless($client->role?->code === Role::AGENCY, 404);
        DB::transaction(function () use ($client) {
            $client = User::query()->lockForUpdate()->findOrFail($client->id);
            abort_unless($client->role?->code === Role::AGENCY, 404);
            $this->revokeAccess($client);
            $client->is_active = false;
            $client->save();
            DB::table('password_reset_tokens')->where('email', $client->email)->delete();
            $client->delete();
        });

        return to_route('clients.index')->with('status', 'Client removed. Existing forms and responses have been retained.');
    }

    private function revokeAccess(User $client): void
    {
        $client->setRememberToken(Str::random(60));
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $client->id)->delete();
        }
    }
}
