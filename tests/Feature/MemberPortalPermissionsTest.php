<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPortalPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.test')->first() ?? tap(new User(), function (User $user) {
            $user->forceFill(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'Password123!', 'is_active' => true,
                'is_super_admin' => true, 'role_id' => Role::where('name', 'super-admin')->value('id')])->save();
        });
    }

    /** An approved member with a login (Member role). Returns a fresh user each time. */
    private function memberUser(): User
    {
        $user = User::where('email', 'asha@example.test')->first();

        if (! $user) {
            $user = new User();
            $user->forceFill(['name' => 'Asha Verma', 'email' => 'asha@example.test', 'password' => 'Password123!', 'is_active' => true,
                'role_id' => Role::where('name', 'member')->value('id')])->save();

            $member = Member::factory()->approved()->create(['full_name' => 'Asha Verma', 'email' => 'asha@example.test']);
            $member->forceFill(['user_id' => $user->id, 'membership_number' => 'LM-2026-0001'])->save();
        }

        return $user->fresh();
    }

    private function role(string $name): Role
    {
        return Role::where('name', $name)->first();
    }

    private function permId(string $name): int
    {
        return Permission::where('name', $name)->value('id');
    }

    private function names(Role $role): array
    {
        return $role->permissions()->pluck('name')->sort()->values()->all();
    }

    // ---------- Editing the Member role ----------

    public function test_member_role_starts_with_both_portal_permissions(): void
    {
        $this->assertSame(['portal.download_documents', 'portal.edit_profile'], $this->names($this->role('member')));
    }

    public function test_super_admin_can_open_and_edit_the_member_role(): void
    {
        $member = $this->role('member');

        $this->actingAs($this->admin())->get("/admin/roles/{$member->id}/edit")
            ->assertOk()->assertSee('Save changes')->assertSee('Member portal')
            ->assertSee('Edit their own contact details')->assertSee('Download their own ID card')
            ->assertDontSee('Approve or reject members')->assertDontSee('Enter the admin panel');
    }

    public function test_member_role_can_have_portal_permissions_turned_off_and_on(): void
    {
        $member = $this->role('member');
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/roles/{$member->id}", [
            'label' => 'Member',
            'permissions' => [$this->permId('portal.edit_profile')],
        ])->assertRedirect('/admin/roles');

        $this->assertSame(['portal.edit_profile'], $this->names($member->fresh()));

        $this->actingAs($admin)->put("/admin/roles/{$member->id}", ['label' => 'Member']);
        $this->assertSame([], $this->names($member->fresh()));
    }

    public function test_member_role_can_never_get_admin_permissions(): void
    {
        $member = $this->role('member');

        $this->actingAs($this->admin())->put("/admin/roles/{$member->id}", [
            'label' => 'Member',
            'permissions' => [
                $this->permId('admin.access'), $this->permId('members.view'), $this->permId('members.approve'),
                $this->permId('settings.manage'), $this->permId('roles.manage'), $this->permId('portal.edit_profile'),
            ],
        ]);

        $this->assertSame(['portal.edit_profile'], $this->names($member->fresh()));

        // So a member login still cannot enter the admin panel.
        $this->actingAs($this->memberUser())->get('/admin/dashboard')->assertForbidden();
    }

    public function test_staff_roles_can_never_get_portal_permissions(): void
    {
        $admin = $this->admin();
        $manager = $this->role('manager');
        $before = $this->names($manager);

        $this->actingAs($admin)->put("/admin/roles/{$manager->id}", [
            'label' => 'Manager',
            'permissions' => [$this->permId('members.view'), $this->permId('portal.edit_profile'), $this->permId('portal.download_documents')],
        ]);

        $this->assertSame(['admin.access', 'members.view'], $this->names($manager->fresh()));

        // Only-portal ticks on a staff role give nothing, and no panel access either.
        $this->actingAs($admin)->post('/admin/roles', ['label' => 'Odd', 'permissions' => [$this->permId('portal.edit_profile')]]);
        $this->assertSame([], $this->names(Role::where('label', 'Odd')->first()));

        // The staff form does not even offer them.
        $this->actingAs($admin)->get('/admin/roles/create')->assertDontSee('Member portal');
        $this->actingAs($admin)->get("/admin/roles/{$manager->id}/edit")->assertDontSee('Edit their own contact details');
    }

    public function test_only_the_super_admin_can_change_the_member_role(): void
    {
        $member = $this->role('member');

        foreach (['manager', 'coordinator'] as $name) {
            $user = new User();
            $user->forceFill(['name' => $name, 'email' => "{$name}@example.test", 'password' => 'Password123!', 'is_active' => true,
                'role_id' => $this->role($name)->id])->save();

            $this->actingAs($user)->put("/admin/roles/{$member->id}", ['label' => 'Member'])->assertForbidden();
        }

        $this->assertSame(['portal.download_documents', 'portal.edit_profile'], $this->names($member->fresh()));
    }

    public function test_member_role_still_cannot_be_deleted_or_given_to_staff(): void
    {
        $admin = $this->admin();
        $member = $this->role('member');

        $this->actingAs($admin)->delete("/admin/roles/{$member->id}")->assertForbidden();
        $this->assertNotNull($member->fresh());

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Priya', 'email' => 'priya@example.test', 'role_id' => $member->id,
            'password' => 'Temporary123', 'password_confirmation' => 'Temporary123',
        ])->assertSessionHasErrors('role_id');
    }

    // ---------- What the switches do ----------

    public function test_with_default_permissions_a_member_can_edit_and_download(): void
    {
        $user = $this->memberUser();

        $this->actingAs($user)->get('/account')->assertOk()->assertSee('Edit my details')->assertSee('Download ID card');
        $this->actingAs($user)->get('/account/profile')->assertOk();
    }

    public function test_turning_off_edit_profile_blocks_editing(): void
    {
        $this->role('member')->permissions()->detach($this->permId('portal.edit_profile'));
        $user = $this->memberUser();

        $this->actingAs($user)->get('/account')->assertOk()->assertDontSee('Edit my details')->assertSee('Download ID card');
        $this->actingAs($user)->get('/account/profile')->assertForbidden();
        $this->actingAs($user)->put('/account/profile', [
            'mobile' => '9811122233', 'address' => 'New', 'city' => 'X', 'state' => 'Y', 'pincode' => '123456',
        ])->assertForbidden();

        $this->assertNotSame('9811122233', Member::where('user_id', $user->id)->value('mobile'));
    }

    public function test_turning_off_downloads_blocks_the_documents(): void
    {
        $this->role('member')->permissions()->detach($this->permId('portal.download_documents'));
        $user = $this->memberUser();

        $this->actingAs($user)->get('/account')->assertOk()
            ->assertDontSee('Download ID card')->assertSee('Document downloads are not available')->assertSee('Edit my details');
        $this->actingAs($user)->get('/account/documents/id-card')->assertForbidden();
        $this->actingAs($user)->get('/account/documents/joining-letter')->assertForbidden();
    }

    public function test_the_member_still_sees_their_profile_with_everything_turned_off(): void
    {
        $this->role('member')->permissions()->detach();

        $this->actingAs($this->memberUser())->get('/account')->assertOk()->assertSee('Asha Verma')->assertSee('LM-2026-0001');
    }

    public function test_changes_apply_on_the_next_request(): void
    {
        $member = $this->role('member');
        $admin = $this->admin();

        $this->actingAs($this->memberUser())->get('/account/profile')->assertOk();

        $this->actingAs($admin)->put("/admin/roles/{$member->id}", ['label' => 'Member', 'permissions' => [$this->permId('portal.download_documents')]]);

        $this->actingAs($this->memberUser())->get('/account/profile')->assertForbidden();
    }

    // ---------- Upgrading an existing installation ----------

    public function test_seeder_hands_the_new_portal_permissions_to_an_existing_member_role_only(): void
    {
        // An install made before these permissions existed.
        Permission::whereIn('name', ['portal.edit_profile', 'portal.download_documents'])->delete();
        $this->assertSame([], $this->names($this->role('member')));

        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(['portal.download_documents', 'portal.edit_profile'], $this->names($this->role('member')));
        $this->assertNotContains('portal.edit_profile', $this->names($this->role('manager')));
        $this->assertNotContains('portal.edit_profile', $this->names($this->role('coordinator')));
    }

    public function test_verify_page_downloads_are_not_affected_by_portal_permissions(): void
    {
        $this->role('member')->permissions()->detach();

        $this->get('/verify')->assertOk();
    }
}
