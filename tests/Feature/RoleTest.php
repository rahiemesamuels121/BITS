<?php

namespace Tests\Feature;

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_codes_are_seeded_without_duplicate_rows_on_repeat_runs(): void
    {
        $this->seed(RoleSeeder::class);

        $agencyId = Role::query()->where('code', Role::AGENCY)->sole()->id;
        $adminId = Role::query()->where('code', Role::ADMIN)->sole()->id;

        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseHas('roles', ['id' => $agencyId, 'code' => 'agency', 'name' => 'Agency']);
        $this->assertDatabaseHas('roles', ['id' => $adminId, 'code' => 'admin', 'name' => 'Admin']);
    }

    public function test_duplicate_role_codes_are_rejected_by_the_database(): void
    {
        Role::query()->create(['code' => Role::ADMIN, 'name' => 'Admin']);

        $this->expectException(QueryException::class);

        Role::query()->create(['code' => Role::ADMIN, 'name' => 'Another admin']);
    }
}
