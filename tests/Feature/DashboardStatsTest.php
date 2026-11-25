<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
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

    private function withStatus(string $status, int $count = 1, array $attributes = []): void
    {
        Member::factory()->count($count)->create($attributes)->each(
            fn (Member $m) => $m->forceFill(['status' => $status])->save()
        );
    }

    private function stat(string $name, int $value): string
    {
        return 'data-stat="' . $name . '">' . $value . '<';
    }

    public function test_counts_per_status_are_shown(): void
    {
        $this->withStatus('pending', 3);
        $this->withStatus('approved', 2);
        $this->withStatus('blocked', 1);

        $this->actingAs($this->makeUser('manager'))->get('/admin/dashboard')
            ->assertOk()
            ->assertSee($this->stat('pending', 3), false)
            ->assertSee($this->stat('approved', 2), false)
            ->assertSee($this->stat('total', 6), false)
            ->assertSee('status=pending', false)
            ->assertSee('Pending applications');
    }

    public function test_monthly_numbers_use_the_right_dates(): void
    {
        // Added this month: 2. Added long ago: 1.
        $this->withStatus('pending', 2);
        $old = Member::factory()->create();
        $old->forceFill(['created_at' => now()->subMonths(3)])->save();

        $this->actingAs($this->makeUser('coordinator'))->get('/admin/dashboard')
            ->assertSee($this->stat('new-this-month', 2), false)
            ->assertSee($this->stat('total', 3), false);
    }

    public function test_approved_this_month_counts_the_first_approval_date_only(): void
    {
        $manager = $this->makeUser('manager');
        $admin = $this->makeUser('super-admin', true);
        $a = Member::factory()->create();
        $b = Member::factory()->create();

        $this->assertNull($a->fresh()->approved_at);

        $this->actingAs($manager)->post("/admin/members/{$a->id}/approve");
        $this->actingAs($manager)->post("/admin/members/{$b->id}/approve");

        $first = $a->fresh()->approved_at;
        $this->assertNotNull($first);

        // Later status changes must not move or clear the approval date.
        $this->travelTo(now()->addDays(2));
        $this->actingAs($admin)->post("/admin/members/{$a->id}/deactivate");
        $this->actingAs($admin)->post("/admin/members/{$a->id}/restore");
        $this->travelBack();

        $this->assertTrue($a->fresh()->approved_at->equalTo($first));

        $this->actingAs($manager)->get('/admin/dashboard')->assertSee($this->stat('approved-this-month', 2), false);

        // An approval from an earlier month is not counted.
        $b->forceFill(['approved_at' => now()->subMonths(2)])->save();
        $this->actingAs($manager)->get('/admin/dashboard')->assertSee($this->stat('approved-this-month', 1), false);
    }

    public function test_approved_at_cannot_be_mass_assigned(): void
    {
        $member = Member::create([
            'full_name' => 'Test', 'father_name' => 'Father', 'date_of_birth' => '1990-01-01', 'gender' => 'male',
            'mobile' => '9800000000', 'address' => 'Somewhere', 'city' => 'City', 'state' => 'State', 'pincode' => '123456',
            'approved_at' => now(),
        ]);

        $this->assertNull($member->fresh()->approved_at);
    }

    public function test_recent_list_shows_only_the_latest_five_with_links(): void
    {
        foreach (range(1, 7) as $i) {
            Member::factory()->create(['full_name' => "Person Number {$i}"]);
        }

        $response = $this->actingAs($this->makeUser('manager'))->get('/admin/dashboard');

        $response->assertSee('Person Number 7')->assertSee('Person Number 3')
            ->assertDontSee('Person Number 2')->assertDontSee('Person Number 1');

        $latest = Member::where('full_name', 'Person Number 7')->first();
        $response->assertSee("/admin/members/{$latest->id}", false);
    }

    public function test_empty_system_shows_zeros_and_a_friendly_message(): void
    {
        $this->actingAs($this->makeUser('manager'))->get('/admin/dashboard')
            ->assertOk()
            ->assertSee($this->stat('total', 0), false)
            ->assertSee('No members yet');
    }

    public function test_staff_card_is_only_for_users_who_manage_users(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $this->makeUser('coordinator', false, 'c1@example.test');
        $this->makeUser('manager', false, 'm1@example.test', ['is_active' => false]);

        // 3 staff accounts in total (admin, coordinator, manager); 2 active.
        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertSee('data-stat="staff">2<', false)->assertSee('/ 3')->assertSee('Active staff users');

        $this->actingAs($this->makeUser('manager', false, 'm2@example.test'))->get('/admin/dashboard')
            ->assertDontSee('Active staff users');
    }

    public function test_member_logins_are_not_counted_as_staff(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $memberUser = $this->makeUser('member', false, 'asha@example.test');
        Member::factory()->approved()->create(['email' => 'asha@example.test'])->forceFill(['user_id' => $memberUser->id])->save();

        $this->actingAs($admin)->get('/admin/dashboard')->assertSee('data-stat="staff">1<', false);
    }

    public function test_user_without_members_view_sees_no_member_data(): void
    {
        Member::factory()->create(['full_name' => 'Secret Person']);

        $role = Role::create(['name' => 'visitor', 'label' => 'Visitor', 'is_system' => false]);
        $role->permissions()->sync(Permission::where('name', 'admin.access')->pluck('id'));
        $user = $this->makeUser('manager', false, 'visitor@example.test', ['role_id' => $role->id]);

        $this->actingAs($user)->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('Secret Person')
            ->assertDontSee('data-stat=', false)
            ->assertDontSee('Pending applications')
            ->assertSee('You have access to the admin panel');
    }

    public function test_staff_only_user_sees_just_the_staff_card(): void
    {
        Member::factory()->create(['full_name' => 'Secret Person']);

        $role = Role::create(['name' => 'hr', 'label' => 'HR', 'is_system' => false]);
        $role->permissions()->sync(Permission::whereIn('name', ['admin.access', 'users.manage'])->pluck('id'));
        $user = $this->makeUser('manager', false, 'hr@example.test', ['role_id' => $role->id]);

        $this->actingAs($user)->get('/admin/dashboard')
            ->assertOk()->assertSee('Active staff users')->assertDontSee('Secret Person')->assertDontSee('Pending applications');
    }

    public function test_demo_members_get_an_approval_date_when_approved_like(): void
    {
        $this->artisan('app:demo-members', ['count' => 30])->assertSuccessful();

        $this->assertSame(0, Member::whereNotNull('membership_number')->whereNull('approved_at')->count());
        $this->assertSame(0, Member::whereIn('status', ['pending', 'rejected'])->whereNotNull('approved_at')->count());
    }
}
