<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberListTest extends TestCase
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

    public function test_guest_is_redirected_and_member_role_is_forbidden(): void
    {
        $this->get('/admin/members')->assertRedirect('/login');
        $this->actingAs($this->makeUser('member'))->get('/admin/members')->assertForbidden();
    }

    public function test_user_with_admin_access_but_without_members_view_is_forbidden(): void
    {
        $role = Role::create(['name' => 'editor', 'label' => 'Editor']);
        $role->permissions()->attach(\App\Models\Permission::where('name', 'admin.access')->value('id'));

        $user = new User();
        $user->forceFill([
            'name' => 'Editor', 'email' => 'editor@example.test', 'password' => 'Password123!',
            'is_active' => true, 'role_id' => $role->id,
        ])->save();

        $this->actingAs($user)->get('/admin/dashboard')->assertOk();
        $this->actingAs($user)->get('/admin/members')->assertForbidden();
    }

    public function test_super_admin_manager_and_coordinator_can_view_the_list(): void
    {
        Member::factory()->create(['full_name' => 'Asha Verma']);

        $this->actingAs($this->makeUser('super-admin', true))->get('/admin/members')->assertOk()->assertSee('Asha Verma');
        $this->actingAs($this->makeUser('manager'))->get('/admin/members')->assertOk()->assertSee('Asha Verma');
        $this->actingAs($this->makeUser('coordinator'))->get('/admin/members')->assertOk()->assertSee('Asha Verma');
    }

    public function test_aadhaar_is_masked_in_the_list(): void
    {
        Member::factory()->withAadhaar('123456789012')->create();

        $this->actingAs($this->makeUser('manager'))->get('/admin/members')
            ->assertOk()
            ->assertSee('XXXX XXXX 9012')
            ->assertDontSee('123456789012');
    }

    public function test_search_by_name_mobile_and_membership_number(): void
    {
        $a = Member::factory()->create(['full_name' => 'Ravi Kumar', 'mobile' => '9811111111']);
        Member::factory()->create(['full_name' => 'Sunita Rao', 'mobile' => '9822222222']);
        $a->forceFill(['membership_number' => 'LM-2026-0007'])->save();

        $admin = $this->makeUser('manager');

        $this->actingAs($admin)->get('/admin/members?q=ravi')->assertSee('Ravi Kumar')->assertDontSee('Sunita Rao');
        $this->actingAs($admin)->get('/admin/members?q=9822222222')->assertSee('Sunita Rao')->assertDontSee('Ravi Kumar');
        $this->actingAs($admin)->get('/admin/members?q=LM-2026-0007')->assertSee('Ravi Kumar')->assertDontSee('Sunita Rao');
    }

    public function test_search_treats_percent_sign_literally(): void
    {
        Member::factory()->create(['full_name' => 'Ravi Kumar']);

        $this->actingAs($this->makeUser('manager'))->get('/admin/members?q=%25')
            ->assertOk()->assertDontSee('Ravi Kumar')->assertSee('No members found');
    }

    public function test_status_filter_and_unknown_status_is_ignored(): void
    {
        Member::factory()->create(['full_name' => 'Pending Person']);
        Member::factory()->approved()->create(['full_name' => 'Approved Person']);

        $admin = $this->makeUser('manager');

        $this->actingAs($admin)->get('/admin/members?status=approved')
            ->assertSee('Approved Person')->assertDontSee('Pending Person');

        $this->actingAs($admin)->get('/admin/members?status=nonsense')
            ->assertSee('Approved Person')->assertSee('Pending Person');
    }

    public function test_results_are_paginated_and_keep_the_filters(): void
    {
        Member::factory()->count(20)->create(['city' => 'Testville']);

        $admin = $this->makeUser('manager');

        $page1 = $this->actingAs($admin)->get('/admin/members?status=pending');
        $page1->assertOk()->assertSee('Showing 1 to 15 of 20');
        $this->assertSame(15, substr_count($page1->getContent(), 'Testville'));
        $page1->assertSee('status=pending&amp;page=2', false);

        $page2 = $this->actingAs($admin)->get('/admin/members?status=pending&page=2');
        $this->assertSame(5, substr_count($page2->getContent(), 'Testville'));
    }

    public function test_sidebar_link_follows_permission(): void
    {
        $this->actingAs($this->makeUser('coordinator'))->get('/admin/dashboard')->assertSee('Members');
    }

    public function test_demo_command_creates_and_clears_demo_members_only(): void
    {
        $real = Member::factory()->create(['full_name' => 'Real Person']);

        $this->artisan('app:demo-members', ['count' => 6])->assertSuccessful();
        $this->assertSame(7, Member::count());

        $this->artisan('app:demo-members', ['--clear' => true])->assertSuccessful();
        $this->assertSame(1, Member::count());
        $this->assertTrue(Member::first()->is($real));
    }
}
