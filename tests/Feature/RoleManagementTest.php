<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $roleName, bool $super = false, string $email = null): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'User ' . $roleName,
            'email' => $email ?? $roleName . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => $super,
            'role_id' => Role::where('name', $roleName)->value('id'),
        ])->save();

        return $user;
    }

    private function permId(string $name): int
    {
        return Permission::where('name', $name)->value('id');
    }

    private function names(Role $role): array
    {
        return $role->permissions()->pluck('name')->sort()->values()->all();
    }

    // ---------- Access ----------

    public function test_only_the_super_admin_can_open_the_roles_screens(): void
    {
        $role = Role::where('name', 'manager')->first();

        foreach (['manager', 'coordinator', 'member'] as $name) {
            $user = $this->makeUser($name);
            $this->actingAs($user)->get('/admin/roles')->assertForbidden();
            $this->actingAs($user)->get('/admin/roles/create')->assertForbidden();
            $this->actingAs($user)->post('/admin/roles', ['label' => 'Hacker'])->assertForbidden();
            $this->actingAs($user)->put("/admin/roles/{$role->id}", ['label' => 'Hacked'])->assertForbidden();
            $this->actingAs($user)->delete("/admin/roles/{$role->id}")->assertForbidden();
        }

        auth()->logout();
        $this->get('/admin/roles')->assertRedirect('/login');
    }

    public function test_holding_roles_manage_through_a_role_still_does_not_unlock_the_screens(): void
    {
        $manager = Role::where('name', 'manager')->first();
        $manager->permissions()->attach($this->permId('roles.manage'));

        $this->actingAs($this->makeUser('manager'))->get('/admin/roles')->assertForbidden();
    }

    public function test_super_admin_sees_the_list_and_the_sidebar_link(): void
    {
        $admin = $this->makeUser('super-admin', true);

        $this->actingAs($admin)->get('/admin/roles')
            ->assertOk()->assertSee('Super Admin')->assertSee('Manager')->assertSee('Coordinator')->assertSee('Member');

        $this->actingAs($admin)->get('/admin/dashboard')->assertSee('Roles &amp; permissions', false);
        $this->actingAs($this->makeUser('manager'))->get('/admin/dashboard')->assertDontSee('Roles &amp; permissions', false);
    }

    public function test_reserved_permissions_are_not_offered_in_the_form(): void
    {
        $admin = $this->makeUser('super-admin', true);

        $this->actingAs($admin)->get('/admin/roles/create')
            ->assertOk()->assertDontSee('Manage roles and permissions')->assertDontSee('View the audit log')
            ->assertSee('Approve or reject members');
    }

    // ---------- Create ----------

    public function test_super_admin_can_create_a_custom_role(): void
    {
        $admin = $this->makeUser('super-admin', true);

        $this->actingAs($admin)->post('/admin/roles', [
            'label' => 'Field Officer',
            'description' => 'Adds members in the field',
            'permissions' => [$this->permId('members.create')],
        ])->assertRedirect('/admin/roles')->assertSessionHas('success');

        $role = Role::where('label', 'Field Officer')->firstOrFail();

        $this->assertSame('field-officer', $role->name);
        $this->assertFalse($role->is_system);
        // Panel access and viewing are added automatically.
        $this->assertSame(['admin.access', 'members.create', 'members.view'], $this->names($role));
    }

    public function test_reserved_and_unknown_permissions_cannot_be_smuggled_in(): void
    {
        $admin = $this->makeUser('super-admin', true);

        $this->actingAs($admin)->post('/admin/roles', [
            'label' => 'Sneaky',
            'permissions' => [$this->permId('roles.manage'), $this->permId('audit.view'), $this->permId('settings.manage')],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['admin.access', 'settings.manage'], $this->names(Role::where('label', 'Sneaky')->first()));

        $this->actingAs($admin)->post('/admin/roles', ['label' => 'Broken', 'permissions' => [99999]])
            ->assertSessionHasErrors('permissions.0');
        $this->assertFalse(Role::where('label', 'Broken')->exists());
    }

    public function test_role_without_permissions_is_allowed_and_names_must_be_unique(): void
    {
        $admin = $this->makeUser('super-admin', true);

        $this->actingAs($admin)->post('/admin/roles', ['label' => 'Observer'])->assertSessionHasNoErrors();
        $this->assertSame([], $this->names(Role::where('label', 'Observer')->first()));

        $this->actingAs($admin)->post('/admin/roles', ['label' => 'Observer'])->assertSessionHasErrors('label');
        $this->actingAs($admin)->post('/admin/roles', ['label' => 'Manager'])->assertSessionHasErrors('label');
        $this->actingAs($admin)->post('/admin/roles', ['label' => ''])->assertSessionHasErrors('label');
    }

    public function test_slug_collisions_get_a_number(): void
    {
        $admin = $this->makeUser('super-admin', true);

        $this->actingAs($admin)->post('/admin/roles', ['label' => 'Super  Admin!']);
        $this->assertTrue(Role::where('name', 'super-admin-2')->exists());
    }

    // ---------- Edit ----------

    public function test_editing_a_role_reaches_new_accounts_but_not_existing_ones(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $coordinator = Role::where('name', 'coordinator')->first();
        $existing = $this->makeUser('coordinator', false, 'existing@example.test');
        $member = Member::factory()->create(['full_name' => 'Asha Verma']);

        $this->actingAs($existing)->get("/admin/members/{$member->id}/edit")->assertForbidden();

        $this->actingAs($admin)->put("/admin/roles/{$coordinator->id}", [
            'label' => 'Coordinator',
            'permissions' => [$this->permId('members.edit')],
        ])->assertRedirect('/admin/roles');

        $this->assertSame(['admin.access', 'members.edit', 'members.view'], $this->names($coordinator->fresh()));

        // The role is only a preset: the existing account keeps its own grants until the super admin changes them.
        $this->actingAs($existing->fresh())->get("/admin/members/{$member->id}/edit")->assertForbidden();

        // A new account on the role starts with the updated preset.
        $newcomer = $this->makeUser('coordinator', false, 'newcomer@example.test');
        $this->actingAs($newcomer->fresh())->get("/admin/members/{$member->id}/edit")->assertOk();
    }

    public function test_manager_can_be_given_the_block_permission(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $manager = Role::where('name', 'manager')->first();
        $user = $this->makeUser('manager');
        $member = Member::factory()->approved()->create();

        $this->actingAs($user)->post("/admin/members/{$member->id}/block", ['note' => 'Misconduct'])->assertForbidden();

        // Access is per account: the super admin grants the block permission to this manager only.
        $this->actingAs($admin)->put(route('access.update', $user), [
            'permissions' => [$this->permId('members.deactivate')],
        ]);

        $this->actingAs($user->fresh())->post("/admin/members/{$member->id}/block", ['note' => 'Misconduct'])->assertRedirect();
        $this->assertSame(Member::STATUS_BLOCKED, $member->fresh()->status);
    }

    public function test_label_can_be_changed_but_the_internal_name_stays(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $manager = Role::where('name', 'manager')->first();

        $this->actingAs($admin)->put("/admin/roles/{$manager->id}", ['label' => 'Branch Manager', 'description' => 'New text']);

        $fresh = $manager->fresh();
        $this->assertSame('Branch Manager', $fresh->label);
        $this->assertSame('manager', $fresh->name);
        $this->assertTrue($fresh->is_system);
    }

    public function test_edit_form_shows_current_values(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $manager = Role::where('name', 'manager')->first();

        $this->actingAs($admin)->get("/admin/roles/{$manager->id}/edit")
            ->assertOk()->assertSee('value="Manager"', false)->assertSee('Save changes');
    }

    // ---------- Locked roles ----------

    public function test_super_admin_role_cannot_be_changed(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $role = Role::where('name', 'super-admin')->first();
        $before = $this->names($role);

        $this->actingAs($admin)->get("/admin/roles/{$role->id}/edit")->assertOk()->assertDontSee('Save changes');
        $this->actingAs($admin)->put("/admin/roles/{$role->id}", [
            'label' => 'Renamed',
            'permissions' => [$this->permId('members.view')],
        ])->assertForbidden();

        $this->assertSame($before, $this->names($role->fresh()));
        $this->assertNotSame('Renamed', $role->fresh()->label);
    }

    // ---------- Delete ----------

    public function test_system_roles_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('super-admin', true);

        foreach (['super-admin', 'manager', 'coordinator', 'member'] as $name) {
            $role = Role::where('name', $name)->first();
            $this->actingAs($admin)->delete("/admin/roles/{$role->id}")->assertForbidden();
            $this->assertNotNull($role->fresh());
        }
    }

    public function test_custom_role_can_be_deleted_only_when_empty(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $role = Role::create(['name' => 'temp', 'label' => 'Temp', 'is_system' => false]);

        $user = new User();
        $user->forceFill(['name' => 'T', 'email' => 't@example.test', 'password' => 'Password123!', 'is_active' => true, 'role_id' => $role->id])->save();

        $this->actingAs($admin)->delete("/admin/roles/{$role->id}")->assertSessionHasErrors('role');
        $this->assertNotNull($role->fresh());

        $user->forceFill(['role_id' => null])->save();

        $this->actingAs($admin)->delete("/admin/roles/{$role->id}")->assertRedirect('/admin/roles')->assertSessionHas('success');
        $this->assertNull(Role::find($role->id));
    }
}
