<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminPermissionsSeeder::class);
    }

    private function makeUser(string $roleName, bool $super = false): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'User ' . $roleName,
            'email' => $roleName . '-' . uniqid() . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => $super,
            'role_id' => Role::where('name', $roleName)->value('id'),
        ])->save();

        return $user;
    }

    /** A staff account that may open the admin panel and manage the site settings (nothing else). */
    private function siteManager(): User
    {
        $staff = $this->makeUser('coordinator');
        $staff->directPermissions()->sync(Permission::whereIn('name', ['admin.access', 'site.manage'])->pluck('id'));

        return $staff;
    }

    public function test_public_pages_send_browser_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_only_the_super_admin_can_change_tracking_code(): void
    {
        $staff = $this->siteManager();

        $this->actingAs($staff)->put('/admin/site-config', [
            'pwa' => ['display' => 'standalone'],
            'tracking' => ['ga' => '<script>alert(1)</script>'],
        ])->assertRedirect();

        $this->assertNull(SiteSetting::get('tracking.ga'));
    }

    public function test_the_super_admin_can_change_tracking_code(): void
    {
        $super = $this->makeUser('super-admin', true);

        $this->actingAs($super)->put('/admin/site-config', [
            'pwa' => ['display' => 'standalone'],
            'tracking' => ['ga' => 'G-TEST123'],
        ])->assertRedirect();

        $this->assertSame('G-TEST123', SiteSetting::get('tracking.ga'));
    }

    public function test_tracking_fields_are_disabled_for_staff(): void
    {
        $staff = $this->siteManager();

        $this->actingAs($staff)->get('/admin/site-config')
            ->assertOk()
            ->assertSee('name="tracking[ga]"', false)
            ->assertSee('disabled', false);
    }
}
