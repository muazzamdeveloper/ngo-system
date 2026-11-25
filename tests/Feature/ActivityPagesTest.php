<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_list_shows_progress_and_donate_button(): void
    {
        $campaign = Campaign::query()->create(['title' => 'Arogya Sarv', 'slug' => 'arogya-sarv', 'goal_amount' => 400000, 'status' => 'active']);

        $this->get('/campaigns')
            ->assertOk()
            ->assertSee('Arogya Sarv')
            ->assertSee('Donate now')
            ->assertSee('campaign=' . $campaign->id, false);
    }

    public function test_event_list_shows_date_badge_and_only_active_events(): void
    {
        Event::query()->create(['title' => 'Winter Food Drive', 'slug' => 'winter-food-drive', 'event_date' => '2026-12-05', 'status' => 'active']);
        Event::query()->create(['title' => 'Old Drive', 'slug' => 'old-drive', 'event_date' => '2025-01-05', 'status' => 'inactive']);

        $this->get('/events')
            ->assertOk()
            ->assertSee('Winter Food Drive')
            ->assertSee('05 Dec 2026')
            ->assertDontSee('Old Drive');
    }
}
