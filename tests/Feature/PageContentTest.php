<?php

namespace Tests\Feature;

use App\Models\PageBlock;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminPermissionsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminPermissionsSeeder::class);
    }

    private function user(string $role, bool $super = false): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'User ' . $role,
            'email' => $role . '-' . uniqid() . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'is_super_admin' => $super,
            'role_id' => Role::where('name', $role)->value('id'),
        ])->save();

        return $user;
    }

    public function test_config_page_shows_default_text_until_saved(): void
    {
        $this->get('/page/about-organisation')
            ->assertOk()
            ->assertSee('Our Mission')
            ->assertSee('Sarv Abhyudaya Foundation');
    }

    public function test_saved_text_replaces_the_default_on_the_public_page(): void
    {
        $admin = $this->user('manager', true);

        $this->actingAs($admin)->put(route('content.update', 'about-organisation'), [
            'blocks' => [
                'mission_title' => 'Our Purpose',
                'mission' => '<p>Changed mission text.</p><script>alert(1)</script>',
            ],
        ])->assertRedirect();

        $this->get('/page/about-organisation')
            ->assertSee('Our Purpose')
            ->assertSee('Changed mission text.')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_image_upload_is_stored_and_shown(): void
    {
        $admin = $this->user('manager', true);
        $file = \Illuminate\Http\UploadedFile::fake()->image('mission.jpg', 800, 500);

        $this->actingAs($admin)->put(route('content.update', 'about-organisation'), [
            'images' => ['mission_image' => $file],
        ])->assertRedirect();

        $block = PageBlock::query()->where(['page_key' => 'about-organisation', 'block_key' => 'mission_image'])->firstOrFail();
        $this->assertNotNull($block->image_path);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        $admin = $this->user('manager', true);
        $file = \Illuminate\Http\UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf');

        $this->actingAs($admin)->put(route('content.update', 'about-organisation'), [
            'images' => ['mission_image' => $file],
        ])->assertSessionHasErrors('images.mission_image');
    }

    public function test_unknown_page_is_not_found(): void
    {
        $this->get('/page/no-such-page')->assertNotFound();
        $this->actingAs($this->user('manager', true))->get('/admin/content/no-such-page')->assertNotFound();
    }

    public function test_staff_without_content_permission_cannot_edit(): void
    {
        $this->actingAs($this->user('manager'))->get(route('content.index'))->assertForbidden();
        $this->actingAs($this->user('manager'))->put(route('content.update', 'about-organisation'), ['blocks' => ['mission_title' => 'x']])->assertForbidden();
    }
}
