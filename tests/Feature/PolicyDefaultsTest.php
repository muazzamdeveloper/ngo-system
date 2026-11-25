<?php

namespace Tests\Feature;

use App\Models\Policy;
use App\Models\PolicySection;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_added_once_and_shown_publicly(): void
    {
        $this->seed(PolicyDefaultsSeeder::class);
        $this->seed(PolicyDefaultsSeeder::class);

        $this->assertSame(5, Policy::query()->count());
        $privacy = Policy::query()->where('type', 'privacy')->firstOrFail();
        $this->assertSame(4, PolicySection::query()->where('policy_id', $privacy->id)->count());

        $this->get('/policy/privacy')->assertOk()->assertSee('Our Commitment to Privacy');
    }

    public function test_edited_policy_is_not_overwritten_by_the_seeder(): void
    {
        $this->seed(PolicyDefaultsSeeder::class);
        $privacy = Policy::query()->where('type', 'privacy')->firstOrFail();
        PolicySection::query()->where('policy_id', $privacy->id)->update(['title' => 'Edited by admin']);

        $this->seed(PolicyDefaultsSeeder::class);

        $this->assertSame(0, PolicySection::query()->where('policy_id', $privacy->id)->where('title', 'Our Commitment to Privacy')->count());
        $this->get('/policy/privacy')->assertSee('Edited by admin');
    }
}
