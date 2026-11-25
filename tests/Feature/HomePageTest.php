<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Event;
use App\Models\GalleryVideo;
use App\Models\PressRelease;
use App\Models\Testimonial;
use App\Support\YouTube;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_site_shows_editable_demo_content_and_no_real_records(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Upcoming Events')
            ->assertSee('No upcoming events')
            ->assertSee('Lord Jagannath')
            ->assertSee('Arogya Sarv');
    }

    public function test_home_shows_real_records_and_editable_headings(): void
    {
        Event::query()->create(['title' => 'Winter Food Drive', 'event_date' => now()->addDays(5), 'status' => 'active', 'slug' => 'winter-food-drive']);
        Campaign::query()->create(['title' => 'Arogya Sarv', 'slug' => 'arogya-sarv', 'goal_amount' => 400000, 'status' => 'active']);
        PressRelease::query()->create(['title' => 'Vocational Centre Opens', 'slug' => 'vocational-centre', 'release_date' => now(), 'excerpt' => 'New centre.', 'status' => 'active']);
        GalleryVideo::query()->create(['title' => 'Video one', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ', 'status' => 'active']);
        Testimonial::query()->create(['name' => 'Rekha Devi', 'message' => 'Thank you.', 'rating' => 5, 'status' => 'active']);
        Donation::query()->create(['donor_name' => 'Asha Devi', 'donor_phone' => '9876543210', 'amount' => 500, 'method' => 'upi', 'status' => 'completed', 'source' => 'admin']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Winter Food Drive')
            ->assertSee('Arogya Sarv')
            ->assertSee('Vocational Centre Opens')
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Rekha Devi')
            ->assertSee('Asha Devi');
    }

    public function test_youtube_helper_accepts_common_links_only(): void
    {
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', YouTube::embedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', YouTube::embedUrl('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertNull(YouTube::embedUrl('https://example.com/video'));
        $this->assertNull(YouTube::embedUrl(null));
    }
}
