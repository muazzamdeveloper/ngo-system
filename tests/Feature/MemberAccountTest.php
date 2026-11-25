<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\MemberAccountCreated;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MemberAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $roleName, bool $super = false, array $extra = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'User ' . $roleName,
            'email' => $roleName . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => $super,
            'role_id' => Role::where('name', $roleName)->value('id'),
        ], $extra))->save();

        return $user;
    }

    private function approvedMember(array $attributes = []): Member
    {
        return Member::factory()->approved()->create(array_merge(['email' => 'asha@example.test', 'full_name' => 'Asha Verma'], $attributes));
    }

    private function url(Member $member): string
    {
        return "/admin/members/{$member->id}/account";
    }

    // ---------- Who can create accounts ----------

    public function test_manager_and_super_admin_can_create_accounts_but_coordinator_cannot(): void
    {
        $member = $this->approvedMember();

        $this->actingAs($this->makeUser('coordinator'))->post($this->url($member), ['password' => 'Temp12345'])->assertForbidden();
        $this->assertNull($member->fresh()->user_id);

        $this->actingAs($this->makeUser('manager'))->post($this->url($member), ['password' => 'Temp12345'])->assertRedirect();
        $this->assertNotNull($member->fresh()->user_id);

        $other = $this->approvedMember(['email' => 'other@example.test']);
        $this->actingAs($this->makeUser('super-admin', true))->post($this->url($other), ['password' => 'Temp12345'])->assertRedirect();
        $this->assertNotNull($other->fresh()->user_id);
    }

    // ---------- Creating the account ----------

    public function test_account_is_created_linked_and_notified(): void
    {
        Notification::fake();
        $member = $this->approvedMember();

        $this->actingAs($this->makeUser('manager'))
            ->post($this->url($member), ['password' => 'Temp12345', 'send_email' => '1'])
            ->assertRedirect("/admin/members/{$member->id}")
            ->assertSessionHas('success')
            ->assertSessionMissing('new_password');

        $user = $member->fresh()->user;

        $this->assertSame('asha@example.test', $user->email);
        $this->assertSame('Asha Verma', $user->name);
        $this->assertSame(Role::where('name', 'member')->value('id'), $user->role_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
        $this->assertFalse($user->is_super_admin);
        $this->assertTrue(Hash::check('Temp12345', $user->password));
        $this->assertNotSame('Temp12345', $user->getRawOriginal('password'));

        Notification::assertSentTo($user, MemberAccountCreated::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            return $notification->password === 'Temp12345'
                && str_contains(implode(' ', $mail->introLines), 'Temp12345')
                && str_contains(implode(' ', $mail->introLines), 'asha@example.test');
        });
    }

    public function test_no_email_is_sent_when_the_box_is_not_ticked(): void
    {
        Notification::fake();
        $member = $this->approvedMember();

        $this->actingAs($this->makeUser('manager'))->post($this->url($member), ['password' => 'Temp12345']);

        Notification::assertNothingSent();
        $this->assertNotNull($member->fresh()->user_id);
    }

    public function test_blank_password_generates_one_that_is_shown_once(): void
    {
        $member = $this->approvedMember();

        $response = $this->actingAs($this->makeUser('manager'))->post($this->url($member), []);

        $password = session('new_password');
        $this->assertNotNull($password);
        $this->assertSame(12, strlen($password));
        $this->assertTrue(Hash::check($password, $member->fresh()->user->password));

        $this->get("/admin/members/{$member->id}")->assertSee($password);
        $this->get("/admin/members/{$member->id}")->assertDontSee($password);
    }

    public function test_weak_passwords_are_rejected(): void
    {
        $member = $this->approvedMember();
        $manager = $this->makeUser('manager');

        foreach (['short1', 'onlyletters', '123456789'] as $weak) {
            $this->actingAs($manager)->post($this->url($member), ['password' => $weak])->assertSessionHasErrors('password');
        }

        $this->assertNull($member->fresh()->user_id);
    }

    public function test_rules_for_who_can_get_an_account(): void
    {
        $manager = $this->makeUser('manager');

        $pending = Member::factory()->create(['email' => 'p@example.test']);
        $this->actingAs($manager)->post($this->url($pending), ['password' => 'Temp12345'])->assertSessionHasErrors('account');

        $noEmail = Member::factory()->approved()->create(['email' => null]);
        $this->actingAs($manager)->post($this->url($noEmail), ['password' => 'Temp12345'])->assertSessionHasErrors('account');

        $taken = $this->approvedMember(['email' => 'manager@example.test']);
        $this->actingAs($manager)->post($this->url($taken), ['password' => 'Temp12345'])->assertSessionHasErrors('account');

        $done = $this->approvedMember(['email' => 'done@example.test']);
        $this->actingAs($manager)->post($this->url($done), ['password' => 'Temp12345']);
        $this->actingAs($manager)->post($this->url($done), ['password' => 'Temp12345'])->assertSessionHasErrors('account');

        $this->assertNull($pending->fresh()->user_id);
        $this->assertNull($noEmail->fresh()->user_id);
        $this->assertNull($taken->fresh()->user_id);
        $this->assertSame(1, User::where('email', 'done@example.test')->count());
    }

    public function test_create_login_form_is_shown_only_when_it_applies(): void
    {
        $manager = $this->makeUser('manager');
        $approved = $this->approvedMember();
        $pending = Member::factory()->create();

        $this->actingAs($manager)->get("/admin/members/{$approved->id}")->assertSee('Create login');
        $this->actingAs($manager)->get("/admin/members/{$pending->id}")->assertDontSee('Create login')->assertSee('Only approved members can get a login account');
        $this->actingAs($this->makeUser('coordinator'))->get("/admin/members/{$approved->id}")->assertDontSee('Create login');
    }

    // ---------- First login and password change ----------

    public function test_member_must_change_the_temporary_password_before_anything_else(): void
    {
        $member = $this->approvedMember();
        $this->actingAs($this->makeUser('manager'))->post($this->url($member), ['password' => 'Temp12345']);
        auth()->logout();

        $this->post('/login', ['email' => 'asha@example.test', 'password' => 'Temp12345'])->assertRedirect('/account');

        $this->get('/account')->assertRedirect('/account/password');
        $this->get('/admin/dashboard')->assertRedirect('/account/password');
        $this->get('/account/password')->assertOk()->assertSee('temporary password');

        // Wrong current password and weak new passwords are rejected.
        $this->put('/account/password', ['current_password' => 'wrong', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertSessionHasErrors('current_password');
        $this->put('/account/password', ['current_password' => 'Temp12345', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
        $this->put('/account/password', ['current_password' => 'Temp12345', 'password' => 'Temp12345', 'password_confirmation' => 'Temp12345'])
            ->assertSessionHasErrors('password');
        $this->assertTrue($member->fresh()->user->must_change_password);

        $this->put('/account/password', ['current_password' => 'Temp12345', 'password' => 'BrandNew123', 'password_confirmation' => 'BrandNew123'])
            ->assertRedirect('/account');

        $user = $member->fresh()->user;
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('BrandNew123', $user->password));

        $this->get('/account')->assertOk()->assertSee($member->fresh()->status === 'approved' ? 'Approved' : '')
            ->assertSee('Asha Verma');
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_member_can_log_out_even_with_a_temporary_password(): void
    {
        $user = $this->makeUser('member', false, ['must_change_password' => true]);

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_account_page_shows_membership_details(): void
    {
        $member = $this->approvedMember();
        $member->forceFill(['membership_number' => 'LM-2026-0042'])->save();
        $user = $this->makeUser('member', false, ['email' => 'asha@example.test']);
        $member->forceFill(['user_id' => $user->id])->save();

        $this->actingAs($user)->get('/account')->assertOk()->assertSee('LM-2026-0042')->assertSee('Approved');
    }

    public function test_login_sends_staff_to_dashboard_and_members_to_their_page(): void
    {
        $this->makeUser('manager');
        $this->makeUser('member', false, ['email' => 'member@example.test']);

        $this->post('/login', ['email' => 'manager@example.test', 'password' => 'Password123!'])->assertRedirect('/admin/dashboard');
        auth()->logout();

        $this->post('/login', ['email' => 'member@example.test', 'password' => 'Password123!'])->assertRedirect('/account');

        // Already logged in: the login page forwards to the right home.
        $this->get('/login')->assertRedirect('/account');
    }

    public function test_blocked_member_cannot_log_in(): void
    {
        $member = $this->approvedMember();
        $this->actingAs($this->makeUser('manager'))->post($this->url($member), ['password' => 'Temp12345']);
        auth()->logout();

        $admin = $this->makeUser('super-admin', true);
        $this->actingAs($admin)->post("/admin/members/{$member->id}/block", ['note' => 'Misconduct']);
        auth()->logout();

        $this->post('/login', ['email' => 'asha@example.test', 'password' => 'Temp12345'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ---------- Seeder upgrade ----------

    public function test_seeder_hands_a_new_permission_to_existing_roles_without_undoing_changes(): void
    {
        // Pretend this install was made before the permission existed.
        Permission::where('name', 'members.create_account')->delete();
        $this->assertFalse(Role::where('name', 'manager')->first()->permissions->contains('name', 'members.create_account'));

        // The super admin had customised the coordinator role.
        $coordinator = Role::where('name', 'coordinator')->first();
        $coordinator->permissions()->attach(Permission::where('name', 'members.edit')->value('id'));

        $this->seed(RolePermissionSeeder::class);

        $this->assertTrue(Role::where('name', 'manager')->first()->permissions->contains('name', 'members.create_account'));
        $this->assertFalse($coordinator->fresh()->permissions->contains('name', 'members.create_account'));
        $this->assertTrue($coordinator->fresh()->permissions->contains('name', 'members.edit'));
    }
}
