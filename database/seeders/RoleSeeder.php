<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([Role::AGENCY => 'Agency', Role::ADMIN => 'Admin'] as $code => $name) {
            Role::query()->updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
