<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $roleName, bool $super = false, ?string $email = null, array $extra = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'User ' . $roleName,
            'email' => $email ?? $roleName . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => $super,
            'role_id' => Role::where('name', $roleName)->value('id'),
        ], $extra))->save();

        return $user;
    }

    private function role(string $label, array $permissions): Role
    {
        $role = Role::create(['name' => str($label)->slug(), 'label' => $label, 'is_system' => false]);
        $role->permissions()->sync(Permission::whereIn('name', $permissions)->pluck('id'));

        return $role;
    }

    private function validData(Role $role, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Priya Sharma',
            'email' => 'priya@example.test',
            'role_id' => $role->id,
            'password' => 'Temporary123',
            'password_confirmation' => 'Temporary123',
        ], $overrides);
    }

    // ---------- Access ----------

    public function test_users_screens_need_the_users_manage_permission(): void
    {
        foreach (['manager', 'coordinator', 'member'] as $name) {
            $this->actingAs($this->makeUser($name))->get('/admin/users')->assertForbidden();
        }

        $this->actingAs($this->makeUser('super-admin', true))->get('/admin/users')->assertOk();

        $hr = $this->role('HR', ['admin.access', 'users.manage']);
        $this->actingAs($this->makeUser('manager', false, 'hr@example.test', ['role_id' => $hr->id]))->get('/admin/users')->assertOk();

        auth()->logout();
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_sidebar_link_follows_the_permission(): void
    {
        $this->actingAs($this->makeUser('super-admin', true))->get('/admin/dashboard')->assertSee('Staff users');
        $this->actingAs($this->makeUser('manager'))->get('/admin/dashboard')->assertDontSee('Staff users');
    }

    // ---------- Create ----------

    public function test_super_admin_can_create_a_staff_user(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $manager = Role::where('name', 'manager')->first();

        $this->actingAs($admin)->post('/admin/users', $this->validData($manager))
            ->assertRedirect('/admin/users')->assertSessionHas('success');

        $user = User::where('email', 'priya@example.test')->firstOrFail();
        $this->assertSame($manager->id, $user->role_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
        $this->assertFalse($user->is_super_admin);
        $this->assertTrue(Hash::check('Temporary123', $user->password));
    }

    public function test_new_staff_user_must_change_the_password_at_first_login(): void
    {
        $manager = Role::where('name', 'manager')->first();
        $this->actingAs($this->makeUser('super-admin', true))->post('/admin/users', $this->validData($manager));
        auth()->logout();

        $this->post('/login', ['email' => 'priya@example.test', 'password' => 'Temporary123'])->assertRedirect('/admin/dashboard');
        $this->get('/admin/dashboard')->assertRedirect('/account/password');
    }

    public function test_locked_roles_and_bad_input_are_rejected(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $manager = Role::where('name', 'manager')->first();

        foreach (['super-admin', 'member'] as $locked) {
            $this->actingAs($admin)->post('/admin/users', $this->validData(Role::where('name', $locked)->first()))
                ->assertSessionHasErrors('role_id');
        }

        $this->actingAs($admin)->post('/admin/users', $this->validData($manager, ['role_id' => 99999]))->assertSessionHasErrors('role_id');
        $this->actingAs($admin)->post('/admin/users', $this->validData($manager, ['email' => 'not-an-email']))->assertSessionHasErrors('email');
        $this->actingAs($admin)->post('/admin/users', $this->validData($manager, ['email' => 'super-admin@example.test']))->assertSessionHasErrors('email');
        $this->actingAs($admin)->post('/admin/users', $this->validData($manager, ['password' => 'short1', 'password_confirmation' => 'short1']))->assertSessionHasErrors('password');
        $this->actingAs($admin)->post('/admin/users', $this->validData($manager, ['password_confirmation' => 'Different123']))->assertSessionHasErrors('password');

        $this->assertFalse(User::where('email', 'priya@example.test')->exists());
    }

    public function test_super_admin_flag_cannot_be_injected(): void
    {
        $manager = Role::where('name', 'manager')->first();

        $this->actingAs($this->makeUser('super-admin', true))
            ->post('/admin/users', $this->validData($manager, ['is_super_admin' => 1, 'is_active' => 0, 'must_change_password' => 0]));

        $user = User::where('email', 'priya@example.test')->firstOrFail();
        $this->assertFalse($user->is_super_admin);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
    }

    // ---------- Nobody can hand out more power than they have ----------

    public function test_delegated_user_can_only_give_roles_within_their_own_permissions(): void
    {
        $hr = $this->role('HR', ['admin.access', 'users.manage']);
        $viewerOnly = $this->role('Visitor', ['admin.access']);
        $manager = Role::where('name', 'manager')->first();

        $delegate = $this->makeUser('manager', false, 'hr@example.test', ['role_id' => $hr->id]);

        $this->actingAs($delegate)->post('/admin/users', $this->validData($manager))->assertSessionHasErrors('role_id');
        $this->assertFalse(User::where('email', 'priya@example.test')->exists());

        $this->actingAs($delegate)->post('/admin/users', $this->validData($viewerOnly))->assertSessionHasNoErrors();
        $this->assertSame($viewerOnly->id, User::where('email', 'priya@example.test')->value('role_id'));

        $options = $this->actingAs($delegate)->get('/admin/users/create')->getContent();
        $this->assertStringContainsString('Visitor', $options);
        $this->assertStringNotContainsString('>Manager<', $options);
    }

    public function test_delegated_user_cannot_touch_stronger_accounts_super_admin_or_themselves(): void
    {
        $hr = $this->role('HR', ['admin.access', 'users.manage']);
        $delegate = $this->makeUser('manager', false, 'hr@example.test', ['role_id' => $hr->id]);
        $strong = $this->makeUser('manager', false, 'strong@example.test');
        $admin = $this->makeUser('super-admin', true);

        foreach ([$strong, $admin, $delegate] as $target) {
            $this->actingAs($delegate)->get("/admin/users/{$target->id}/edit")->assertForbidden();
            $this->actingAs($delegate)->post("/admin/users/{$target->id}/deactivate")->assertForbidden();
            $this->assertTrue($target->fresh()->is_active);
        }
    }

    public function test_nobody_can_change_the_super_admin_or_their_own_account_here(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $other = $this->makeUser('super-admin', true, 'second-admin@example.test');

        $this->actingAs($admin)->get("/admin/users/{$admin->id}/edit")->assertForbidden();
        $this->actingAs($admin)->post("/admin/users/{$admin->id}/deactivate")->assertForbidden();
        $this->actingAs($admin)->get("/admin/users/{$other->id}/edit")->assertForbidden();
        $this->actingAs($admin)->post("/admin/users/{$other->id}/deactivate")->assertForbidden();
        $this->assertTrue($other->fresh()->is_active);
    }

    // ---------- List ----------

    public function test_list_shows_staff_but_not_member_logins(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $this->makeUser('coordinator', false, 'coord@example.test', ['name' => 'Cora Coordinator']);

        $memberUser = $this->makeUser('member', false, 'asha@example.test', ['name' => 'Asha Member']);
        Member::factory()->approved()->create(['user_id' => $memberUser->id, 'full_name' => 'Asha Member', 'email' => 'asha@example.test'])
            ->forceFill(['user_id' => $memberUser->id])->save();

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()->assertSee('Cora Coordinator')->assertSee('Super Admin')->assertDontSee('Asha Member');

        $this->actingAs($admin)->get("/admin/users/{$memberUser->id}/edit")->assertNotFound();
        $this->actingAs($admin)->post("/admin/users/{$memberUser->id}/deactivate")->assertNotFound();
    }

    // ---------- Edit ----------

    public function test_super_admin_can_change_name_email_and_role(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $staff = $this->makeUser('coordinator', false, 'coord@example.test');
        $manager = Role::where('name', 'manager')->first();
        $hash = $staff->password;

        $this->actingAs($admin)->get("/admin/users/{$staff->id}/edit")->assertOk()->assertSee('coord@example.test');

        $this->actingAs($admin)->put("/admin/users/{$staff->id}", [
            'name' => 'New Name', 'email' => 'new@example.test', 'role_id' => $manager->id,
        ])->assertRedirect('/admin/users');

        $fresh = $staff->fresh();
        $this->assertSame('New Name', $fresh->name);
        $this->assertSame('new@example.test', $fresh->email);
        $this->assertSame($manager->id, $fresh->role_id);
        $this->assertSame($hash, $fresh->password);               // blank password keeps the old one
        $this->assertFalse($fresh->must_change_password);
    }

    public function test_password_reset_forces_a_new_change_and_email_must_stay_unique(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $staff = $this->makeUser('coordinator', false, 'coord@example.test');
        $coordinator = Role::where('name', 'coordinator')->first();

        $this->actingAs($admin)->put("/admin/users/{$staff->id}", [
            'name' => $staff->name, 'email' => 'coord@example.test', 'role_id' => $coordinator->id,
            'password' => 'ResetPass123', 'password_confirmation' => 'ResetPass123',
        ])->assertSessionHasNoErrors();

        $fresh = $staff->fresh();
        $this->assertTrue(Hash::check('ResetPass123', $fresh->password));
        $this->assertTrue($fresh->must_change_password);

        $this->actingAs($admin)->put("/admin/users/{$staff->id}", [
            'name' => $staff->name, 'email' => 'super-admin@example.test', 'role_id' => $coordinator->id,
        ])->assertSessionHasErrors('email');
    }

    public function test_update_cannot_assign_locked_roles(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $staff = $this->makeUser('coordinator', false, 'coord@example.test');

        foreach (['super-admin', 'member'] as $locked) {
            $this->actingAs($admin)->put("/admin/users/{$staff->id}", [
                'name' => $staff->name, 'email' => $staff->email, 'role_id' => Role::where('name', $locked)->value('id'),
            ])->assertSessionHasErrors('role_id');
        }

        $this->assertSame(Role::where('name', 'coordinator')->value('id'), $staff->fresh()->role_id);
        $this->assertFalse($staff->fresh()->is_super_admin);
    }

    // ---------- Disable / enable ----------

    public function test_disabled_staff_user_is_locked_out_and_can_be_enabled_again(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $staff = $this->makeUser('coordinator', false, 'coord@example.test');

        $this->actingAs($admin)->post("/admin/users/{$staff->id}/deactivate")->assertRedirect('/admin/users')->assertSessionHas('success');
        $this->assertFalse($staff->fresh()->is_active);

        // An open session is ended on the next request, and logging in is refused.
        $this->actingAs($staff->fresh())->get('/admin/dashboard')->assertRedirect('/login');
        auth()->logout();
        $this->post('/login', ['email' => 'coord@example.test', 'password' => 'Password123!'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)->post("/admin/users/{$staff->id}/activate")->assertSessionHas('success');
        $this->assertTrue($staff->fresh()->is_active);

        auth()->logout();
        $this->post('/login', ['email' => 'coord@example.test', 'password' => 'Password123!'])->assertRedirect('/admin/dashboard');
    }
}
