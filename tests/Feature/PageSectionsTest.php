<?php

namespace Tests\Feature;

use App\Models\PageBlock;
use App\Support\PageContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_are_grouped_into_sections_in_declared_order(): void
    {
        $sections = PageContent::sections('about-organisation');
        $keys = array_column($sections, 'key');

        $this->assertSame(
            ['intro', 'mission', 'vision', 'focus', 'leadership', 'impact', 'why', 'programmes'],
            $keys
        );

        $programmes = collect($sections)->firstWhere('key', 'programmes');
        $this->assertSame('cards', $programmes['layout']);
        $this->assertArrayHasKey('card1_title', $programmes['blocks']);
    }

    public function test_block_without_section_goes_into_content_section(): void
    {
        config(['site_pages.test-page' => [
            'title' => 'Test',
            'blocks' => [
                'loose' => ['type' => 'text', 'label' => 'Loose', 'default' => 'x'],
            ],
        ]]);

        $sections = PageContent::sections('test-page');

        $this->assertCount(1, $sections);
        $this->assertSame('content', $sections[0]['key']);
        $this->assertSame('text', $sections[0]['layout']);
    }

    public function test_image_uses_demo_photo_until_admin_uploads_one(): void
    {
        $url = PageContent::image('about-organisation', 'mission_image');

        $this->assertNotNull($url);
        $this->assertStringContainsString('images/demo/about-mission.jpg', $url);
    }

    public function test_saved_image_path_replaces_demo_photo(): void
    {
        PageBlock::query()->create([
            'page_key' => 'about-organisation',
            'block_key' => 'mission_image',
            'value' => null,
            'image_path' => 'uploads/pages/custom.jpg',
        ]);

        $url = PageContent::image('about-organisation', 'mission_image');

        $this->assertStringNotContainsString('images/demo/about-mission.jpg', (string) $url);
    }

    public function test_demo_photos_exist_in_public_folder(): void
    {
        foreach (['about-intro', 'about-mission', 'about-vision', 'about-focus', 'leader-president'] as $name) {
            $this->assertFileExists(public_path("images/demo/{$name}.jpg"));
        }
    }
}
