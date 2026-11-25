<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\OrganizationSetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $roleName, bool $super = false): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'User ' . $roleName,
            'email' => $roleName . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => $super,
            'role_id' => Role::where('name', $roleName)->value('id'),
        ])->save();

        return $user;
    }

    private function url(Member $member, string $action): string
    {
        return "/admin/members/{$member->id}/{$action}";
    }

    // ---------- Permissions ----------

    public function test_manager_can_decide_applications_but_not_block_or_deactivate(): void
    {
        $manager = $this->makeUser('manager');
        $pending = Member::factory()->create();
        $approved = Member::factory()->approved()->create();

        $this->actingAs($manager)->post($this->url($pending, 'approve'))->assertRedirect();
        $this->assertSame(Member::STATUS_APPROVED, $pending->fresh()->status);

        $this->actingAs($manager)->post($this->url($approved, 'block'), ['note' => 'Misconduct'])->assertForbidden();
        $this->actingAs($manager)->post($this->url($approved, 'deactivate'))->assertForbidden();
        $this->assertSame(Member::STATUS_APPROVED, $approved->fresh()->status);
    }

    public function test_coordinator_and_members_cannot_change_any_status(): void
    {
        $pending = Member::factory()->create();

        foreach (['coordinator', 'member'] as $role) {
            $user = $this->makeUser($role);

            foreach (['approve', 'reject', 'reopen', 'deactivate', 'restore', 'block', 'unblock'] as $action) {
                $this->actingAs($user)->post($this->url($pending, $action), ['note' => 'test note'])->assertForbidden();
            }
        }

        $this->assertSame(Member::STATUS_PENDING, $pending->fresh()->status);

        auth()->logout();
        $this->post($this->url($pending, 'approve'))->assertRedirect('/login');
    }

    // ---------- Approve and membership numbers ----------

    public function test_approval_assigns_number_with_prefix_year_and_sequence(): void
    {
        $manager = $this->makeUser('manager');
        $first = Member::factory()->create();
        $second = Member::factory()->create();
        $year = now()->year;

        $this->actingAs($manager)->post($this->url($first, 'approve'), ['note' => 'Documents OK'])
            ->assertRedirect("/admin/members/{$first->id}")->assertSessionHas('success');

        $first = $first->fresh();
        $this->assertSame("LM-{$year}-0001", $first->membership_number);
        $this->assertSame(Member::STATUS_APPROVED, $first->status);
        $this->assertSame($manager->id, $first->reviewed_by);
        $this->assertNotNull($first->reviewed_at);
        $this->assertSame('Documents OK', $first->status_note);

        $this->actingAs($manager)->post($this->url($second, 'approve'));
        $this->assertSame("LM-{$year}-0002", $second->fresh()->membership_number);
    }

    public function test_changing_the_prefix_affects_only_new_numbers(): void
    {
        $manager = $this->makeUser('manager');
        $a = Member::factory()->create();
        $b = Member::factory()->create();
        $year = now()->year;

        $this->actingAs($manager)->post($this->url($a, 'approve'));
        OrganizationSetting::query()->update(['membership_prefix' => 'HH']);
        $this->actingAs($manager)->post($this->url($b, 'approve'));

        $this->assertSame("LM-{$year}-0001", $a->fresh()->membership_number);
        $this->assertSame("HH-{$year}-0002", $b->fresh()->membership_number);
    }

    public function test_sequence_restarts_every_year(): void
    {
        $manager = $this->makeUser('manager');
        $a = Member::factory()->create();
        $b = Member::factory()->create();

        $this->actingAs($manager)->post($this->url($a, 'approve'));

        $this->travelTo(now()->addYear());
        $this->actingAs($manager)->post($this->url($b, 'approve'));

        $this->assertSame('LM-' . now()->year . '-0001', $b->fresh()->membership_number);
    }

    public function test_existing_numbers_are_never_reused(): void
    {
        $year = now()->year;
        Member::factory()->approved()->create()->forceFill(['membership_number' => "LM-{$year}-0001"])->save();
        $member = Member::factory()->create();

        $this->actingAs($this->makeUser('manager'))->post($this->url($member, 'approve'));

        $this->assertSame("LM-{$year}-0002", $member->fresh()->membership_number);
    }

    public function test_only_pending_members_can_be_approved(): void
    {
        $manager = $this->makeUser('manager');

        foreach ([Member::STATUS_APPROVED, Member::STATUS_REJECTED, Member::STATUS_BLOCKED, Member::STATUS_DEACTIVATED] as $status) {
            $member = Member::factory()->create();
            $member->forceFill(['status' => $status])->save();

            $this->actingAs($manager)->post($this->url($member, 'approve'))->assertSessionHasErrors('status');

            $fresh = $member->fresh();
            $this->assertSame($status, $fresh->status);
            $this->assertNull($fresh->membership_number);
        }
    }

    public function test_approving_twice_does_not_change_the_number(): void
    {
        $manager = $this->makeUser('manager');
        $member = Member::factory()->create();

        $this->actingAs($manager)->post($this->url($member, 'approve'));
        $number = $member->fresh()->membership_number;

        $this->actingAs($manager)->post($this->url($member, 'approve'))->assertSessionHasErrors('status');
        $this->assertSame($number, $member->fresh()->membership_number);
    }

    public function test_demo_members_do_not_use_up_real_numbers(): void
    {
        $this->artisan('app:demo-members', ['count' => 10])->assertSuccessful();

        $member = Member::factory()->create();
        $this->actingAs($this->makeUser('manager'))->post($this->url($member, 'approve'));

        $this->assertSame('LM-' . now()->year . '-0001', $member->fresh()->membership_number);
    }

    // ---------- Reject and reopen ----------

    public function test_reject_needs_a_reason_and_gives_no_number(): void
    {
        $manager = $this->makeUser('manager');
        $member = Member::factory()->create();

        $this->actingAs($manager)->post($this->url($member, 'reject'))->assertSessionHasErrors('note');
        $this->actingAs($manager)->post($this->url($member, 'reject'), ['note' => 'ab'])->assertSessionHasErrors('note');
        $this->assertSame(Member::STATUS_PENDING, $member->fresh()->status);

        $this->actingAs($manager)->post($this->url($member, 'reject'), ['note' => 'Incomplete documents'])->assertSessionHasNoErrors();

        $fresh = $member->fresh();
        $this->assertSame(Member::STATUS_REJECTED, $fresh->status);
        $this->assertSame('Incomplete documents', $fresh->status_note);
        $this->assertNull($fresh->membership_number);
        $this->assertSame($manager->id, $fresh->reviewed_by);
    }

    public function test_rejected_member_can_be_reopened_and_then_approved(): void
    {
        $manager = $this->makeUser('manager');
        $member = Member::factory()->create();

        $this->actingAs($manager)->post($this->url($member, 'reject'), ['note' => 'Missing details']);
        $this->actingAs($manager)->post($this->url($member, 'reopen'))->assertSessionHas('success');
        $this->assertSame(Member::STATUS_PENDING, $member->fresh()->status);

        $this->actingAs($manager)->post($this->url($member, 'approve'));
        $this->assertNotNull($member->fresh()->membership_number);
    }

    public function test_reopen_only_works_on_rejected_members(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->makeUser('manager'))->post($this->url($member, 'reopen'))->assertSessionHasErrors('status');
    }

    // ---------- Block and unblock ----------

    public function test_block_needs_a_reason_disables_login_and_keeps_the_number(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $manager = $this->makeUser('manager');
        $member = Member::factory()->create();

        $account = new User();
        $account->forceFill(['name' => 'Member Login', 'email' => 'login@example.test', 'password' => 'Password123!', 'is_active' => true])->save();
        $member->forceFill(['user_id' => $account->id])->save();

        $this->actingAs($manager)->post($this->url($member, 'approve'));
        $number = $member->fresh()->membership_number;

        $this->actingAs($admin)->post($this->url($member, 'block'))->assertSessionHasErrors('note');
        $this->assertSame(Member::STATUS_APPROVED, $member->fresh()->status);

        $this->actingAs($admin)->post($this->url($member, 'block'), ['note' => 'Misused the ID card'])->assertSessionHasNoErrors();

        $blocked = $member->fresh();
        $this->assertSame(Member::STATUS_BLOCKED, $blocked->status);
        $this->assertSame($number, $blocked->membership_number);
        $this->assertSame($admin->id, $blocked->reviewed_by);
        $this->assertFalse($account->fresh()->is_active);

        $this->actingAs($admin)->post($this->url($member, 'unblock'))->assertSessionHas('success');

        $restored = $member->fresh();
        $this->assertSame(Member::STATUS_APPROVED, $restored->status);
        $this->assertSame($number, $restored->membership_number);
        $this->assertTrue($account->fresh()->is_active);
    }

    public function test_block_and_unblock_only_apply_to_the_right_statuses(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $pending = Member::factory()->create();
        $approved = Member::factory()->approved()->create();

        $this->actingAs($admin)->post($this->url($pending, 'block'), ['note' => 'not allowed'])->assertSessionHasErrors('status');
        $this->actingAs($admin)->post($this->url($approved, 'unblock'))->assertSessionHasErrors('status');
        $this->assertSame(Member::STATUS_PENDING, $pending->fresh()->status);
        $this->assertSame(Member::STATUS_APPROVED, $approved->fresh()->status);
    }

    public function test_number_survives_deactivate_and_restore(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $member = Member::factory()->create();

        $this->actingAs($admin)->post($this->url($member, 'approve'));
        $number = $member->fresh()->membership_number;

        $this->actingAs($admin)->post($this->url($member, 'deactivate'));
        $this->actingAs($admin)->post($this->url($member, 'restore'));

        $this->assertSame($number, $member->fresh()->membership_number);
    }

    // ---------- Buttons on the profile page ----------

    public function test_profile_shows_only_the_actions_that_fit_status_and_permission(): void
    {
        $manager = $this->makeUser('manager');
        $admin = $this->makeUser('super-admin', true);
        $coordinator = $this->makeUser('coordinator');

        $pending = Member::factory()->create();
        $this->actingAs($manager)->get("/admin/members/{$pending->id}")->assertSee('>Approve<', false)->assertSee('>Reject<', false)->assertDontSee('>Block<', false);
        $this->actingAs($coordinator)->get("/admin/members/{$pending->id}")->assertDontSee('>Approve<', false);

        $approved = Member::factory()->approved()->create();
        $this->actingAs($manager)->get("/admin/members/{$approved->id}")->assertDontSee('>Block<', false)->assertDontSee('>Approve<', false);
        $this->actingAs($admin)->get("/admin/members/{$approved->id}")->assertSee('>Block<', false)->assertSee('>Deactivate<', false);

        $blocked = Member::factory()->create();
        $blocked->forceFill(['status' => Member::STATUS_BLOCKED])->save();
        $this->actingAs($admin)->get("/admin/members/{$blocked->id}")->assertSee('>Unblock<', false);

        $rejected = Member::factory()->create();
        $rejected->forceFill(['status' => Member::STATUS_REJECTED])->save();
        $this->actingAs($manager)->get("/admin/members/{$rejected->id}")->assertSee('>Reopen<', false);
    }
}
