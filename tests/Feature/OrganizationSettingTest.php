<?php

namespace Tests\Feature;

use App\Models\OrganizationSetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSettingTest extends TestCase
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

    public function test_default_settings_row_exists_after_migration(): void
    {
        $this->assertDatabaseHas('organization_settings', ['name' => 'Lizer Media', 'membership_prefix' => 'LM']);
        $this->assertSame('LM', OrganizationSetting::current()->initials());
    }

    public function test_initials_use_first_letters_of_first_two_words(): void
    {
        $this->assertSame('HH', (new OrganizationSetting(['name' => 'Helping Hands Trust']))->initials());
        $this->assertSame('S', (new OrganizationSetting(['name' => 'Seva']))->initials());
    }

    public function test_super_admin_can_open_and_update_settings(): void
    {
        $admin = $this->makeUser('super-admin', true);

        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertSee('Organization settings');

        $this->actingAs($admin)->put('/admin/settings', [
            'name' => 'Helping Hands Trust',
            'membership_prefix' => 'hh',
        ])->assertRedirect('/admin/settings');

        $this->assertDatabaseHas('organization_settings', ['name' => 'Helping Hands Trust', 'membership_prefix' => 'HH']);
        $this->assertSame(1, OrganizationSetting::count());

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Helping Hands Trust');
    }

    public function test_invalid_input_is_rejected(): void
    {
        $admin = $this->makeUser('super-admin', true);

        $this->actingAs($admin)->put('/admin/settings', ['name' => '', 'membership_prefix' => 'TOO LONG!'])
            ->assertSessionHasErrors(['name', 'membership_prefix']);

        $this->assertDatabaseHas('organization_settings', ['name' => 'Lizer Media']);
    }

    public function test_manager_and_coordinator_cannot_touch_settings(): void
    {
        foreach (['manager', 'coordinator'] as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)->get('/admin/settings')->assertForbidden();
            $this->actingAs($user)->put('/admin/settings', ['name' => 'Hacked', 'membership_prefix' => 'XX'])->assertForbidden();
        }

        $this->assertDatabaseHas('organization_settings', ['name' => 'Lizer Media']);
    }

    public function test_settings_link_is_hidden_from_users_without_permission(): void
    {
        $this->actingAs($this->makeUser('manager'))->get('/admin/dashboard')
            ->assertOk()->assertDontSee('Organization settings');
    }

    public function test_login_page_shows_organization_name(): void
    {
        $this->get('/login')->assertOk()->assertSee('Lizer Media');
    }
}
