<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateUser extends Command
{
    protected $signature = 'user:create
        {--name= : Account holder name}
        {--email= : Account email address}
        {--role= : Role code: admin or agency}';

    protected $description = 'Create an account with an Agency or Admin role and a privately entered password';

    public function handle(): int
    {
        $attributes = [
            'name' => trim((string) ($this->option('name') ?? $this->ask('Name'))),
            'email' => Str::lower(trim((string) ($this->option('email') ?? $this->ask('Email address')))),
            'role' => $this->option('role') ?? $this->choice('Role', [Role::ADMIN, Role::AGENCY]),
        ];

        $validator = Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'role' => ['required', Rule::in([Role::ADMIN, Role::AGENCY])],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $role = Role::query()->where('code', $attributes['role'])->first();

        if (! $role) {
            $this->error('Seed the role codes first: php artisan db:seed --class=RoleSeeder');

            return self::FAILURE;
        }

        $passwords = [
            'password' => $this->secret('Password (at least 12 characters)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];

        $validator = Validator::make($passwords, [
            'password' => ['required', 'string', 'confirmed', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($attributes, $passwords, $role) {
            $user = new User([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $passwords['password'],
            ]);
            $user->role()->associate($role);
            $user->save();
        });

        $this->info('User created: '.$attributes['email'].' ('.$role->name.').');

        return self::SUCCESS;
    }
}
