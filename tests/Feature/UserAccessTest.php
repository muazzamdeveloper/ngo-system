<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminPermissionsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminPermissionsSeeder::class);
    }

    private function user(string $roleName, bool $super = false, string $email = null): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'User ' . $roleName,
            'email' => $email ?? ($roleName . '-' . uniqid() . '@example.test'),
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => $super,
            'role_id' => Role::where('name', $roleName)->value('id'),
        ])->save();

        return $user;
    }

    private function permissionIds(array $names): array
    {
        return Permission::query()->whereIn('name', $names)->pluck('id')->all();
    }

    public function test_two_managers_on_the_same_role_can_have_different_modules(): void
    {
        $first = $this->user('manager');
        $second = $this->user('manager');

        $first->directPermissions()->sync($this->permissionIds(['donations.manage', 'members.view']));
        $second->directPermissions()->sync($this->permissionIds(['events.manage']));

        $first = User::find($first->id);
        $second = User::find($second->id);

        $this->assertTrue($first->hasPermission('donations.manage'));
        $this->assertFalse($first->hasPermission('events.manage'));
        $this->assertTrue($second->hasPermission('events.manage'));
        $this->assertFalse($second->hasPermission('donations.manage'));
    }

    public function test_super_admin_saves_one_managers_access_without_touching_another(): void
    {
        $super = $this->user('manager', true);
        $target = $this->user('manager');
        $other = $this->user('manager');
        $other->directPermissions()->sync($this->permissionIds(['events.manage']));

        $this->actingAs($super)->put(route('access.update', $target), [
            'permissions' => $this->permissionIds(['campaigns.manage']),
        ])->assertRedirect();

        $this->assertTrue(User::find($target->id)->hasPermission('campaigns.manage'));
        $this->assertTrue(User::find($other->id)->hasPermission('events.manage'));
        $this->assertFalse(User::find($other->id)->hasPermission('campaigns.manage'));
    }

    public function test_staff_manager_cannot_open_or_change_access(): void
    {
        $manager = $this->user('manager');
        $target = $this->user('manager');

        $this->actingAs($manager)->get(route('access.index'))->assertForbidden();
        $this->actingAs($manager)->put(route('access.update', $target), [
            'permissions' => $this->permissionIds(['donations.manage']),
        ])->assertForbidden();

        $this->assertFalse(User::find($target->id)->hasPermission('donations.manage'));
    }

    public function test_super_only_permissions_are_never_granted(): void
    {
        $super = $this->user('manager', true);
        $target = $this->user('manager');

        $this->actingAs($super)->put(route('access.update', $target), [
            'permissions' => $this->permissionIds(['roles.manage', 'audit.view', 'events.manage']),
        ]);

        $target = User::find($target->id);
        $this->assertFalse($target->hasPermission('roles.manage'));
        $this->assertFalse($target->hasPermission('audit.view'));
        $this->assertTrue($target->hasPermission('events.manage'));
    }

    public function test_super_admin_and_member_accounts_are_not_editable_here(): void
    {
        $super = $this->user('manager', true);
        $member = $this->user('member');

        $this->actingAs($super)->get(route('access.edit', $super))->assertNotFound();
        $this->actingAs($super)->get(route('access.edit', $member))->assertNotFound();
    }

    public function test_copying_a_preset_only_changes_that_account(): void
    {
        $super = $this->user('manager', true);
        $target = $this->user('manager');
        $other = $this->user('manager');
        $other->directPermissions()->sync($this->permissionIds(['events.manage']));

        $role = Role::where('name', 'manager')->firstOrFail();

        $this->actingAs($super)->post(route('access.preset', $target), ['role_id' => $role->id])->assertRedirect();

        $this->assertEquals(
            $role->permissions()->pluck('permissions.id')->sort()->values()->all(),
            User::find($target->id)->directPermissions()->pluck('permissions.id')->sort()->values()->all(),
        );
        $this->assertTrue(User::find($other->id)->hasPermission('events.manage'));
    }

    public function test_member_portal_accounts_still_use_their_role(): void
    {
        $member = $this->user('member');
        $role = Role::where('name', 'member')->firstOrFail();

        $this->assertEquals(
            $role->permissions()->pluck('name')->sort()->values()->all(),
            collect($member->fresh()->permissionNames())->sort()->values()->all(),
        );
    }
}
