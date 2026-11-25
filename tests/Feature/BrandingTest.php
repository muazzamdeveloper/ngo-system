<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\OrganizationSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\CardPdfService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function user(string $role, bool $super = false): User
    {
        return User::where('email', "{$role}@example.test")->first() ?? tap(new User(), function (User $user) use ($role, $super) {
            $user->forceFill(['name' => ucfirst($role), 'email' => "{$role}@example.test", 'password' => 'Password123!', 'is_active' => true,
                'is_super_admin' => $super, 'role_id' => Role::where('name', $role)->value('id')])->save();
        });
    }

    private function admin(): User
    {
        return $this->user('super-admin', true);
    }

    private function data(array $overrides = []): array
    {
        return array_merge(['name' => 'Lizer Media', 'membership_prefix' => 'LM'], $overrides);
    }

    private function save(array $overrides = [])
    {
        return $this->actingAs($this->admin())->put('/admin/settings', $this->data($overrides));
    }

    // ---------- Saving ----------

    public function test_settings_page_shows_the_branding_fields(): void
    {
        $this->actingAs($this->admin())->get('/admin/settings')
            ->assertOk()->assertSee('Site title')->assertSee('Tagline')->assertSee('Logo')
            ->assertSee('Site icon')->assertSee('Main colour');
    }

    public function test_only_users_with_the_settings_permission_can_change_branding(): void
    {
        $this->actingAs($this->user('manager'))->put('/admin/settings', $this->data(['site_title' => 'Hacked']))->assertForbidden();
        $this->assertNull(OrganizationSetting::first()->site_title);
    }

    public function test_text_colour_logo_and_icon_are_saved(): void
    {
        $this->save([
            'site_title' => 'Lizer Media - Press', 'tagline' => 'Voice of the people', 'primary_color' => '#0b5394',
            'logo' => UploadedFile::fake()->image('logo.png', 300, 100),
            'favicon' => UploadedFile::fake()->image('icon.png', 64, 64),
        ])->assertRedirect('/admin/settings')->assertSessionHasNoErrors();

        $s = OrganizationSetting::first();
        $this->assertSame('Lizer Media - Press', $s->site_title);
        $this->assertSame('Voice of the people', $s->tagline);
        $this->assertSame('#0b5394', $s->primary_color);
        $this->assertStringStartsWith('branding/', $s->logo_path);
        $this->assertStringStartsWith('branding/', $s->favicon_path);
        Storage::disk('local')->assertExists($s->logo_path);
        Storage::disk('local')->assertExists($s->favicon_path);
        $this->assertSame(1, OrganizationSetting::count());
    }

    public function test_file_paths_cannot_be_set_from_the_form(): void
    {
        $this->save(['logo_path' => '../../.env', 'favicon_path' => 'etc/passwd']);

        $this->assertNull(OrganizationSetting::first()->logo_path);
        $this->assertNull(OrganizationSetting::first()->favicon_path);
    }

    public function test_bad_files_and_colours_are_rejected(): void
    {
        foreach ([
            ['logo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain')],
            ['logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')],
            ['logo' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')],
            ['logo' => UploadedFile::fake()->image('big.png', 100, 100)->size(3000)],
            ['favicon' => UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml')],
            ['favicon' => UploadedFile::fake()->image('big.png', 100, 100)->size(600)],
            ['primary_color' => 'red'],
            ['primary_color' => '#12345'],
            ['primary_color' => '#ffff00'],      // too light for white text
            ['primary_color' => '#ffffff'],
            ['site_title' => str_repeat('a', 101)],
            ['tagline' => str_repeat('a', 161)],
        ] as $override) {
            $this->save($override)->assertSessionHasErrors(array_key_first($override));
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertNull(OrganizationSetting::first()->primary_color);
    }

    public function test_replacing_and_removing_files_deletes_the_old_ones(): void
    {
        $this->save(['logo' => UploadedFile::fake()->image('a.png', 100, 100), 'favicon' => UploadedFile::fake()->image('i.png', 32, 32)]);
        $first = OrganizationSetting::first();
        [$oldLogo, $oldIcon] = [$first->logo_path, $first->favicon_path];

        $this->save(['logo' => UploadedFile::fake()->image('b.png', 100, 100)]);
        $second = OrganizationSetting::first();
        $this->assertNotSame($oldLogo, $second->logo_path);
        Storage::disk('local')->assertMissing($oldLogo);
        Storage::disk('local')->assertExists($second->logo_path);
        Storage::disk('local')->assertExists($oldIcon);     // untouched

        $this->save(['remove_logo' => '1', 'remove_favicon' => '1']);
        $third = OrganizationSetting::first();
        $this->assertNull($third->logo_path);
        $this->assertNull($third->favicon_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_colour_can_be_reset_and_empty_title_falls_back_to_the_name(): void
    {
        $this->save(['primary_color' => '#0b5394', 'site_title' => 'Custom']);
        $this->assertSame('#0b5394', OrganizationSetting::first()->primaryColor());

        $this->save(['reset_color' => '1', 'site_title' => '']);
        $s = OrganizationSetting::first();
        $this->assertNull($s->primary_color);
        $this->assertSame(OrganizationSetting::DEFAULT_COLOR, $s->primaryColor());
        $this->assertSame('Lizer Media', $s->displayTitle());
    }

    // ---------- Showing it ----------

    public function test_logo_and_icon_are_public_and_404_when_missing(): void
    {
        $this->get('/branding/logo')->assertNotFound();
        $this->get('/branding/icon')->assertNotFound();

        $this->save(['logo' => UploadedFile::fake()->image('logo.png', 200, 80), 'favicon' => UploadedFile::fake()->image('i.png', 32, 32)]);
        auth()->logout();

        $logo = $this->get('/branding/logo');
        $logo->assertOk();
        $this->assertSame('nosniff', $logo->headers->get('X-Content-Type-Options'));
        $this->get('/branding/icon')->assertOk();
    }

    public function test_every_layout_shows_the_logo_icon_title_and_colour(): void
    {
        $this->save([
            'site_title' => 'Lizer Press', 'tagline' => 'Voice of the people', 'primary_color' => '#0b5394',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 80), 'favicon' => UploadedFile::fake()->image('i.png', 32, 32),
        ]);

        // admin panel
        $page = $this->actingAs($this->admin())->get('/admin/dashboard');
        $page->assertOk()->assertSee('/branding/logo', false)->assertSee('/branding/icon', false)
            ->assertSee('Lizer Press', false)->assertSee('--color-primary: #0b5394', false);

        // member area
        $member = $this->user('member');
        $this->actingAs($member)->get('/account')->assertOk()
            ->assertSee('/branding/logo', false)->assertSee('/branding/icon', false)->assertSee('--color-primary: #0b5394', false);

        auth()->logout();

        // login and public pages
        foreach (['/login', '/apply', '/verify'] as $url) {
            $this->get($url)->assertOk()->assertSee('/branding/logo', false)->assertSee('/branding/icon', false)
                ->assertSee('--color-primary: #0b5394', false)->assertSee('Lizer Press', false);
        }

        $this->get('/login')->assertSee('Voice of the people');
    }

    public function test_without_a_logo_the_initials_badge_and_default_colour_are_used(): void
    {
        $this->actingAs($this->admin())->get('/admin/dashboard')
            ->assertOk()->assertDontSee('/branding/logo', false)->assertDontSee('rel="icon"', false)
            ->assertSee('brand-badge', false)->assertSee('>LM<', false)
            ->assertSee('--color-primary: ' . OrganizationSetting::DEFAULT_COLOR, false);
    }

    public function test_texts_are_escaped(): void
    {
        $this->save(['site_title' => '<script>alert(1)</script>', 'tagline' => '<img src=x onerror=alert(2)>']);
        auth()->logout();

        $html = $this->get('/login')->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_dark_shade_is_darker(): void
    {
        $s = new OrganizationSetting(['primary_color' => '#1f6f5c']);

        $this->assertSame('#185748', $s->primaryDark());
        $this->assertSame(OrganizationSetting::DEFAULT_COLOR, (new OrganizationSetting(['primary_color' => 'javascript:x']))->primaryColor());
    }

    // ---------- ID card and letter ----------

    public function test_card_and_letter_use_the_site_logo_when_no_card_logo_is_set(): void
    {
        $member = Member::factory()->create();
        $member->forceFill(['status' => 'approved', 'membership_number' => 'LM-2026-0001', 'approved_at' => now()])->save();

        $service = app(CardPdfService::class);
        $without = $service->idCardHtml($member) . $service->joiningLetterHtml($member);

        $this->save(['logo' => UploadedFile::fake()->image('logo.png', 200, 80)]);

        $with = $service->idCardHtml($member) . $service->joiningLetterHtml($member);

        $this->assertGreaterThan(substr_count($without, 'data:image'), substr_count($with, 'data:image'));
    }
}
