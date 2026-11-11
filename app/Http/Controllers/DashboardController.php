<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Form;
use App\Models\Role;
use App\Models\User;
use App\Services\Forms\ClientResponseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ClientResponseService $responses): View
    {
        $account = $request->user()->loadMissing('role');
        if ($account->usesClientDashboard()) {
            return view('clients.dashboard', [
                'account' => $account,
                'contracts' => $account->contracts()->with('form.publishedVersion')
                    ->latest('contracts.created_at')->orderByDesc('contracts.id')->paginate(10)->withQueryString(),
                'responses' => $responses->query($account)->with(['form.contract', 'version'])
                    ->latest('submitted_at')->orderByDesc('id')->paginate(10, ['*'], 'responses_page')->withQueryString()->fragment('my-responses'),
            ]);
        }

        $isAdmin = $request->user()->isAdmin();
        $contracts = Contract::query()->when(! $isAdmin, fn ($query) => $query->whereHas('clients', fn ($clients) => $clients->where('users.id', $request->user()->id)));
        $forms = Form::query()->when(! $isAdmin, fn ($query) => $query->where('user_id', $request->user()->id));
        $clients = User::query()->whereHas('role', fn ($query) => $query->where('code', Role::AGENCY));
        $formStatusCounts = array_replace(
            ['published' => 0, 'draft' => 0, 'archived' => 0],
            (clone $forms)->selectRaw('status, COUNT(*) AS total')->groupBy('status')
                ->pluck('total', 'status')->map(fn ($count) => (int) $count)->all(),
        );

        $stats = [
            'active_clients' => $isAdmin ? (clone $clients)->where('is_active', true)->count() : null,
            'inactive_clients' => $isAdmin ? (clone $clients)->where('is_active', false)->count() : null,
            'active_contracts' => (clone $contracts)->where('is_active', true)->count(),
            'inactive_contracts' => (clone $contracts)->where('is_active', false)->count(),
            'published_forms' => $formStatusCounts['published'],
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'isAdmin' => $isAdmin,
            'formStatusCounts' => $formStatusCounts,
            'recentContracts' => (clone $contracts)->latest()->limit(6)->get(),
            'publishedForms' => (clone $forms)->where('status', 'published')->with('user')->latest('updated_at')->limit(6)->get(),
        ]);
    }
}
