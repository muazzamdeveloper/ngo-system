<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\Event;
use App\Models\Policy;
use App\Models\Role;
use App\Models\User;
use App\Models\Volunteer;
use Database\Seeders\AdminPermissionsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdminPermissionsSeeder::class);
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

    public function test_guests_are_sent_to_login_for_admin_modules(): void
    {
        $this->get(route('modules.index', 'events'))->assertRedirect();
    }

    public function test_member_role_cannot_open_an_admin_module(): void
    {
        $this->actingAs($this->makeUser('member'))
            ->get(route('modules.index', 'events'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_an_event_and_it_is_slugged(): void
    {
        $admin = $this->makeUser('manager', true);

        $this->actingAs($admin)->post(route('modules.store', 'events'), [
            'title' => 'Blood Donation Camp',
            'event_date' => '2026-12-05',
            'location' => 'Ward 4',
            'status' => 'active',
        ])->assertRedirect();

        $event = Event::query()->where('title', 'Blood Donation Camp')->firstOrFail();
        $this->assertSame('blood-donation-camp', $event->slug);
        $this->assertSame('active', $event->status);
    }

    public function test_module_store_rejects_missing_required_fields(): void
    {
        $admin = $this->makeUser('manager', true);

        $this->actingAs($admin)->post(route('modules.store', 'events'), [
            'title' => '',
            'event_date' => '',
        ])->assertSessionHasErrors(['title', 'event_date']);

        $this->assertSame(0, Event::query()->count());
    }

    public function test_unknown_module_returns_not_found(): void
    {
        $this->actingAs($this->makeUser('manager', true))
            ->get('/admin/m/does-not-exist')
            ->assertNotFound();
    }

    public function test_public_events_page_shows_only_active_events(): void
    {
        Event::query()->create(['title' => 'Visible Camp', 'event_date' => '2026-12-01', 'status' => 'active']);
        Event::query()->create(['title' => 'Hidden Camp', 'event_date' => '2026-12-02', 'status' => 'inactive']);

        $this->get('/events')
            ->assertOk()
            ->assertSee('Visible Camp')
            ->assertDontSee('Hidden Camp');
    }

    public function test_public_donate_form_creates_a_pending_donation(): void
    {
        $this->post(route('donate.store'), [
            'donor_name' => 'Asha Devi',
            'donor_phone' => '9876543210',
            'amount' => '500',
            'method' => 'upi',
        ])->assertRedirect();

        $donation = Donation::query()->firstOrFail();
        $this->assertSame('pending', $donation->status);
        $this->assertSame('public', $donation->source);
        $this->assertNull($donation->receipt_no);
    }

    public function test_public_donate_honeypot_silently_drops_bots(): void
    {
        $this->post(route('donate.store'), [
            'donor_name' => 'Bot',
            'donor_phone' => '9876543210',
            'amount' => '500',
            'method' => 'upi',
            'website' => 'http://spam.example',
        ]);

        $this->assertSame(0, Donation::query()->count());
    }

    public function test_donation_amount_must_be_positive(): void
    {
        $this->post(route('donate.store'), [
            'donor_name' => 'Asha Devi',
            'donor_phone' => '9876543210',
            'amount' => '0',
            'method' => 'upi',
        ])->assertSessionHasErrors('amount');
    }

    public function test_public_volunteer_form_creates_a_pending_volunteer(): void
    {
        $this->post(route('volunteer.store'), [
            'full_name' => 'Ravi Kumar',
            'mobile' => '9123456780',
            'city' => 'Patna',
        ])->assertRedirect();

        $volunteer = Volunteer::query()->firstOrFail();
        $this->assertSame('pending', $volunteer->status);
        $this->assertSame('public', $volunteer->source);
    }

    public function test_volunteer_review_requires_permission(): void
    {
        $volunteer = Volunteer::query()->create([
            'full_name' => 'Ravi Kumar', 'mobile' => '9123456780', 'source' => 'public', 'status' => 'pending',
        ]);

        $this->actingAs($this->makeUser('member'))
            ->post(route('volunteers.review', $volunteer), ['status' => 'approved'])
            ->assertForbidden();

        $this->assertSame('pending', $volunteer->fresh()->status);
    }

    public function test_published_policy_is_public_and_draft_is_not(): void
    {
        $published = Policy::query()->where('type', 'privacy')->first()
            ?? Policy::query()->create(['type' => 'privacy', 'title' => 'Privacy Policy', 'is_published' => true]);
        $published->forceFill(['is_published' => true])->save();

        $this->get(route('policy.show', $published->type))->assertOk()->assertSee('Privacy Policy');

        $published->forceFill(['is_published' => false])->save();
        $this->get(route('policy.show', $published->type))->assertNotFound();
    }

    public function test_receipt_number_is_issued_only_when_a_donation_completes(): void
    {
        $donation = Donation::query()->create([
            'donor_name' => 'Asha Devi', 'donor_phone' => '9876543210', 'amount' => 500,
            'method' => 'upi', 'status' => 'pending', 'source' => 'public',
        ]);
        $this->assertNull($donation->receipt_no);

        $donation->forceFill(['status' => 'completed'])->save();
        $this->assertMatchesRegularExpression('/^NGO-\d{4}-0001$/', (string) $donation->fresh()->receipt_no);
    }

    public function test_security_page_is_for_super_admin_only(): void
    {
        $manager = $this->makeUser('manager');
        $this->actingAs($manager)->get(route('security.edit'))->assertForbidden();
    }
}
