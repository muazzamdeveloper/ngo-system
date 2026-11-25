<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(?string $roleName, array $attributes = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'Test ' . ($roleName ?? 'norole'),
            'email' => ($roleName ?? 'norole') . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'role_id' => $roleName ? Role::where('name', $roleName)->value('id') : null,
        ], $attributes))->save();

        return $user;
    }

    public function test_the_four_default_roles_exist(): void
    {
        foreach (['super-admin', 'manager', 'coordinator', 'member'] as $name) {
            $this->assertDatabaseHas('roles', ['name' => $name]);
        }
    }

    public function test_super_admin_has_every_permission(): void
    {
        $admin = $this->userWithRole('super-admin', ['is_super_admin' => true]);

        foreach (Permission::pluck('name') as $name) {
            $this->assertTrue($admin->can($name), "Super admin should have {$name}");
        }

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_manager_can_approve_but_not_manage_roles(): void
    {
        $manager = $this->userWithRole('manager');

        $this->assertTrue($manager->can('members.approve'));
        $this->assertTrue($manager->can('members.view'));
        $this->assertFalse($manager->can('roles.manage'));
        $this->assertFalse($manager->can('members.view_aadhaar'));
        $this->actingAs($manager)->get('/admin/dashboard')->assertOk();
    }

    public function test_coordinator_can_only_view(): void
    {
        $coordinator = $this->userWithRole('coordinator');

        $this->assertTrue($coordinator->can('members.view'));
        $this->assertFalse($coordinator->can('members.approve'));
        $this->assertFalse($coordinator->can('members.create'));
        $this->actingAs($coordinator)->get('/admin/dashboard')->assertOk();
    }

    public function test_member_and_roleless_users_cannot_enter_admin_panel(): void
    {
        $this->actingAs($this->userWithRole('member'))->get('/admin/dashboard')->assertForbidden();

        $this->actingAs($this->userWithRole(null))->get('/admin/dashboard')->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_deactivated_account_is_logged_out(): void
    {
        $manager = $this->userWithRole('manager', ['is_active' => false]);

        $this->actingAs($manager)->get('/admin/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_seeder_does_not_overwrite_changes_made_by_super_admin(): void
    {
        $coordinator = Role::where('name', 'coordinator')->first();
        $coordinator->permissions()->attach(Permission::where('name', 'members.edit')->value('id'));

        $this->seed(RolePermissionSeeder::class);

        $this->assertTrue($coordinator->fresh()->permissions->contains('name', 'members.edit'));
    }

    public function test_privileged_columns_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'Sneaky',
            'email' => 'sneaky@example.test',
            'password' => 'Password123!',
            'is_super_admin' => true,
            'role_id' => 1,
        ]);

        $this->assertFalse($user->fresh()->is_super_admin);
        $this->assertNull($user->fresh()->role_id);
    }
}
