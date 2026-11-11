<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_codes_are_seeded_without_duplicate_rows_on_repeat_runs(): void
    {
        $this->seed(RoleSeeder::class);

        $agencyId = Role::query()->where('code', Role::AGENCY)->sole()->id;
        $adminId = Role::query()->where('code', Role::ADMIN)->sole()->id;
        $staffId = Role::query()->where('code', Role::STAFF)->sole()->id;

        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseHas('roles', ['id' => $agencyId, 'code' => 'agency', 'name' => 'Agency']);
        $this->assertDatabaseHas('roles', ['id' => $adminId, 'code' => 'admin', 'name' => 'Admin']);
        $this->assertDatabaseHas('roles', ['id' => $staffId, 'code' => 'staff', 'name' => 'Staff']);
    }

    public function test_staff_can_be_selected_for_accounts_without_granting_admin_permissions(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->for(Role::query()->where('code', Role::ADMIN)->sole())->create();
        $client = User::factory()->for(Role::query()->where('code', Role::AGENCY)->sole())->create();
        $staffRole = Role::query()->where('code', Role::STAFF)->sole();

        $this->actingAs($admin)->get(route('clients.create'))->assertOk()->assertSee('Staff');
        $this->get(route('clients.edit', $client))->assertOk()->assertSee('Staff');
        $this->post(route('clients.store'), [
            'name' => 'Staff member', 'email' => 'staff@example.com',
            'password' => 'staff-password-123', 'password_confirmation' => 'staff-password-123',
            'is_active' => true, 'role_id' => $staffRole->id,
        ])->assertRedirect(route('clients.index'));

        $staff = User::query()->where('email', 'staff@example.com')->sole();
        $this->assertSame($staffRole->id, $staff->role_id);
        $this->assertFalse($staff->isAdmin());
        $this->assertFalse(Gate::forUser($staff)->allows('manage-clients'));
        $this->assertFalse(Gate::forUser($staff)->allows('manage-contracts'));
    }

    public function test_duplicate_role_codes_are_rejected_by_the_database(): void
    {
        Role::query()->create(['code' => Role::ADMIN, 'name' => 'Admin']);

        $this->expectException(QueryException::class);

        Role::query()->create(['code' => Role::ADMIN, 'name' => 'Another admin']);
    }
}
