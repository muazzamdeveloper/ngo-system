<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminPermissionsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Every admin page and public module page renders without errors (catches view and route mistakes). */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminPermissionsSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => true,
            'role_id' => Role::where('name', 'manager')->value('id'),
        ])->save();

        return $user;
    }

    public function test_every_module_list_and_form_renders(): void
    {
        $admin = $this->superAdmin();

        foreach (array_keys(config('admin.modules', [])) as $module) {
            $this->actingAs($admin)->get(route('modules.index', $module))->assertOk();
            $this->actingAs($admin)->get(route('modules.create', $module))->assertOk();
        }
    }

    public function test_settings_and_operations_pages_render(): void
    {
        $admin = $this->superAdmin();

        foreach ([
            'dashboard', 'donations.index', 'donations.create', 'volunteers.index',
            'templates.index', 'policies.index', 'site-config.edit', 'themes.index',
            'domains.edit', 'smtp.edit', 'notifications.index', 'gateways.edit',
            'security.edit', 'profile.edit', 'access.index', 'users.index',
        ] as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
        }
    }

    public function test_public_module_and_donation_pages_render(): void
    {
        foreach (['/donate', '/volunteer', '/events', '/campaigns'] as $path) {
            $this->get($path)->assertOk();
        }
    }
}
