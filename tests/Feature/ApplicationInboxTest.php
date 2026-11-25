<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationInboxTest extends TestCase
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

    private function online(array $attributes = []): Member
    {
        return Member::factory()->create(array_merge(['source' => Member::SOURCE_ONLINE], $attributes));
    }

    public function test_guest_and_member_role_cannot_open_the_inbox(): void
    {
        $this->get('/admin/applications')->assertRedirect('/login');
        $this->actingAs($this->makeUser('member'))->get('/admin/applications')->assertForbidden();
    }

    public function test_admin_access_without_members_view_is_forbidden(): void
    {
        $role = Role::create(['name' => 'editor', 'label' => 'Editor']);
        $role->permissions()->attach(Permission::where('name', 'admin.access')->value('id'));

        $user = new User();
        $user->forceFill([
            'name' => 'Editor', 'email' => 'editor@example.test', 'password' => 'Password123!',
            'is_active' => true, 'role_id' => $role->id,
        ])->save();

        $this->actingAs($user)->get('/admin/applications')->assertForbidden();
    }

    public function test_staff_with_members_view_can_open_it(): void
    {
        $this->online(['full_name' => 'Asha Online']);

        foreach (['manager', 'coordinator'] as $roleName) {
            $this->actingAs($this->makeUser($roleName))->get('/admin/applications')
                ->assertOk()->assertSee('Asha Online');
        }

        $this->actingAs($this->makeUser('super-admin', true))->get('/admin/applications')->assertOk();
    }

    public function test_only_online_applications_are_listed(): void
    {
        $this->online(['full_name' => 'Online Person']);
        Member::factory()->create(['full_name' => 'Staff Added Person', 'source' => Member::SOURCE_ADMIN]);

        $this->actingAs($this->makeUser('manager'))->get('/admin/applications')
            ->assertSee('Online Person')->assertDontSee('Staff Added Person');
    }

    public function test_pending_applications_come_first(): void
    {
        $this->online(['full_name' => 'Zed Approved'])->forceFill(['status' => Member::STATUS_APPROVED])->save();
        $this->online(['full_name' => 'Amy Pending']);
        $this->online(['full_name' => 'Newest Rejected'])->forceFill(['status' => Member::STATUS_REJECTED])->save();

        $this->actingAs($this->makeUser('manager'))->get('/admin/applications')
            ->assertSeeInOrder(['Amy Pending', 'Newest Rejected', 'Zed Approved']);
    }

    public function test_status_filter_and_unknown_status_is_ignored(): void
    {
        $this->online(['full_name' => 'Pending Person']);
        $this->online(['full_name' => 'Approved Person'])->forceFill(['status' => Member::STATUS_APPROVED])->save();

        $admin = $this->makeUser('manager');

        $this->actingAs($admin)->get('/admin/applications?status=approved')
            ->assertSee('Approved Person')->assertDontSee('Pending Person');

        $this->actingAs($admin)->get('/admin/applications?status=nonsense')
            ->assertSee('Approved Person')->assertSee('Pending Person');
    }

    public function test_search_by_name_mobile_and_district(): void
    {
        $this->online(['full_name' => 'Ravi Kumar', 'mobile' => '9811111111', 'district' => 'Lucknow']);
        $this->online(['full_name' => 'Sunita Rao', 'mobile' => '9822222222', 'district' => 'Pune']);

        $admin = $this->makeUser('manager');

        $this->actingAs($admin)->get('/admin/applications?q=ravi')->assertSee('Ravi Kumar')->assertDontSee('Sunita Rao');
        $this->actingAs($admin)->get('/admin/applications?q=9822222222')->assertSee('Sunita Rao')->assertDontSee('Ravi Kumar');
        $this->actingAs($admin)->get('/admin/applications?q=Lucknow')->assertSee('Ravi Kumar')->assertDontSee('Sunita Rao');
    }

    public function test_search_treats_percent_sign_literally(): void
    {
        $this->online(['full_name' => 'Ravi Kumar']);

        $this->actingAs($this->makeUser('manager'))->get('/admin/applications?q=%25')
            ->assertOk()->assertDontSee('Ravi Kumar')->assertSee('No applications found');
    }

    public function test_aadhaar_number_is_never_shown_in_the_inbox(): void
    {
        $this->online()->setAadhaar('234567890124')->save();

        $this->actingAs($this->makeUser('manager'))->get('/admin/applications')
            ->assertOk()->assertDontSee('234567890124');
    }

    public function test_document_count_is_shown(): void
    {
        $this->online(['full_name' => 'Doc Person'])->forceFill([
            'aadhaar_front_path' => 'members/documents/a.jpg',
            'aadhaar_back_path' => 'members/documents/b.jpg',
        ])->save();

        $this->actingAs($this->makeUser('manager'))->get('/admin/applications')
            ->assertSee('2 of 4')->assertSee('No payment screenshot');
    }

    public function test_sidebar_and_dashboard_show_only_pending_online_applications(): void
    {
        $this->online();
        $this->online();
        $this->online()->forceFill(['status' => Member::STATUS_APPROVED])->save();
        Member::factory()->create(['source' => Member::SOURCE_ADMIN]); // staff-added, not counted

        $admin = $this->makeUser('manager');

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertSee('data-stat="online-pending">2<', false)
            ->assertSee('data-nav-count="applications">2<', false);
    }

    public function test_no_badge_when_nothing_is_waiting(): void
    {
        $this->actingAs($this->makeUser('manager'))->get('/admin/dashboard')
            ->assertOk()->assertDontSee('data-nav-count', false);
    }
}
