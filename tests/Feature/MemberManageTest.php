<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberManageTest extends TestCase
{
    use RefreshDatabase;

    private const AADHAAR = '234567890124';
    private const OTHER_AADHAAR = '298765432101';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
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

    private function updateData(Member $member, array $overrides = []): array
    {
        return array_merge([
            'full_name' => $member->full_name,
            'father_name' => $member->father_name,
            'date_of_birth' => $member->date_of_birth->format('Y-m-d'),
            'gender' => $member->gender,
            'mobile' => $member->mobile,
            'email' => $member->email,
            'address' => $member->address,
            'city' => $member->city,
            'state' => $member->state,
            'pincode' => $member->pincode,
            'occupation' => $member->occupation,
        ], $overrides);
    }

    // ---------- Show ----------

    public function test_viewers_can_open_the_profile_but_members_and_guests_cannot(): void
    {
        $member = Member::factory()->create(['full_name' => 'Asha Verma']);

        $this->actingAs($this->makeUser('coordinator'))->get("/admin/members/{$member->id}")
            ->assertOk()->assertSee('Asha Verma');

        $this->actingAs($this->makeUser('member'))->get("/admin/members/{$member->id}")->assertForbidden();

        auth()->logout();
        $this->get("/admin/members/{$member->id}")->assertRedirect('/login');
    }

    public function test_unknown_member_gives_404_and_create_is_not_treated_as_an_id(): void
    {
        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->get('/admin/members/9999')->assertNotFound();
        $this->actingAs($manager)->get('/admin/members/create')->assertOk()->assertSee('Save member');
    }

    public function test_profile_shows_masked_aadhaar_only_and_list_links_to_it(): void
    {
        $member = Member::factory()->withAadhaar(self::AADHAAR)->create();
        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->get("/admin/members/{$member->id}")
            ->assertOk()->assertSee('XXXX XXXX 0124')->assertDontSee(self::AADHAAR)
            ->assertDontSee('Show full number');

        $this->actingAs($manager)->get('/admin/members')->assertSee("/admin/members/{$member->id}", false);
    }

    // ---------- Full Aadhaar reveal ----------

    public function test_only_super_admin_with_permission_can_reveal_full_aadhaar(): void
    {
        $member = Member::factory()->withAadhaar(self::AADHAAR)->create();

        foreach (['manager', 'coordinator', 'member'] as $role) {
            $this->actingAs($this->makeUser($role))->post("/admin/members/{$member->id}/aadhaar")->assertForbidden();
        }

        $admin = $this->makeUser('super-admin', true);

        $this->actingAs($admin)->get("/admin/members/{$member->id}")->assertSee('Show full number');

        $response = $this->actingAs($admin)->post("/admin/members/{$member->id}/aadhaar");
        $response->assertOk()->assertSee('2345 6789 0124');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        // A normal page view afterwards is masked again.
        $this->actingAs($admin)->get("/admin/members/{$member->id}")->assertDontSee('2345 6789 0124');
    }

    public function test_reveal_is_logged_without_the_number(): void
    {
        Log::spy();
        $member = Member::factory()->withAadhaar(self::AADHAAR)->create();
        $admin = $this->makeUser('super-admin', true);

        $this->actingAs($admin)->post("/admin/members/{$member->id}/aadhaar")->assertOk();

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) use ($member, $admin) {
            return $message === 'Aadhaar number viewed'
                && $context === ['member_id' => $member->id, 'user_id' => $admin->id]
                && ! str_contains(json_encode($context), self::AADHAAR);
        })->once();
    }

    // ---------- Edit ----------

    public function test_only_users_with_edit_permission_can_edit(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->makeUser('manager'))->get("/admin/members/{$member->id}/edit")->assertOk()->assertSee('Save changes');

        $coordinator = $this->makeUser('coordinator');
        $this->actingAs($coordinator)->get("/admin/members/{$member->id}/edit")->assertForbidden();
        $this->actingAs($coordinator)->put("/admin/members/{$member->id}", $this->updateData($member, ['full_name' => 'Hacked']))->assertForbidden();

        $this->assertNotSame('Hacked', $member->fresh()->full_name);
    }

    public function test_edit_form_is_filled_with_current_values(): void
    {
        $member = Member::factory()->withAadhaar(self::AADHAAR)->create(['full_name' => 'Ravi Kumar', 'city' => 'Nagpur']);

        $this->actingAs($this->makeUser('manager'))->get("/admin/members/{$member->id}/edit")
            ->assertOk()->assertSee('Ravi Kumar')->assertSee('Nagpur')->assertSee('XXXX XXXX 0124');
    }

    public function test_manager_can_update_details(): void
    {
        $member = Member::factory()->create(['full_name' => 'Old Name']);

        $this->actingAs($this->makeUser('manager'))
            ->put("/admin/members/{$member->id}", $this->updateData($member, ['full_name' => 'New Name', 'city' => 'Delhi']))
            ->assertRedirect("/admin/members/{$member->id}")->assertSessionHas('success');

        $this->assertSame('New Name', $member->fresh()->full_name);
        $this->assertSame('Delhi', $member->fresh()->city);
    }

    public function test_keeping_own_email_is_fine_but_anothers_email_is_not(): void
    {
        $member = Member::factory()->create(['email' => 'mine@example.test']);
        Member::factory()->create(['email' => 'taken@example.test']);
        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->put("/admin/members/{$member->id}", $this->updateData($member))->assertSessionHasNoErrors();

        $this->actingAs($manager)->put("/admin/members/{$member->id}", $this->updateData($member, ['email' => 'taken@example.test']))
            ->assertSessionHasErrors('email');
    }

    public function test_aadhaar_blank_keeps_new_replaces_and_duplicates_are_rejected(): void
    {
        $member = Member::factory()->withAadhaar(self::AADHAAR)->create();
        Member::factory()->withAadhaar(self::OTHER_AADHAAR)->create();
        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->put("/admin/members/{$member->id}", $this->updateData($member, ['aadhaar' => '']))->assertSessionHasNoErrors();
        $this->assertSame(self::AADHAAR, $member->fresh()->fullAadhaar());

        // Re-entering the member's own number is fine.
        $this->actingAs($manager)->put("/admin/members/{$member->id}", $this->updateData($member, ['aadhaar' => self::AADHAAR]))->assertSessionHasNoErrors();

        // Another member's number is not.
        $this->actingAs($manager)->put("/admin/members/{$member->id}", $this->updateData($member, ['aadhaar' => self::OTHER_AADHAAR]))
            ->assertSessionHasErrors('aadhaar');
        $this->assertSame(self::AADHAAR, $member->fresh()->fullAadhaar());

        // A fresh valid number replaces the old one.
        $this->actingAs($manager)->put("/admin/members/{$member->id}", $this->updateData($member, ['aadhaar' => '345678901238']))->assertSessionHasNoErrors();
        $this->assertSame('345678901238', $member->fresh()->fullAadhaar());
        $this->assertSame('1238', $member->fresh()->aadhaar_last4);
    }

    public function test_photo_can_be_replaced_and_removed_and_old_files_are_deleted(): void
    {
        $member = Member::factory()->create();
        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->put("/admin/members/{$member->id}", $this->updateData($member, [
            'photo' => UploadedFile::fake()->image('first.jpg', 200, 200),
        ]));
        $first = $member->fresh()->photo_path;
        Storage::disk('local')->assertExists($first);

        $this->actingAs($manager)->put("/admin/members/{$member->id}", $this->updateData($member, [
            'photo' => UploadedFile::fake()->image('second.jpg', 200, 200),
        ]));
        $second = $member->fresh()->photo_path;
        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);

        $this->actingAs($manager)->put("/admin/members/{$member->id}", $this->updateData($member, ['remove_photo' => '1']));
        $this->assertNull($member->fresh()->photo_path);
        Storage::disk('local')->assertMissing($second);
    }

    public function test_update_cannot_change_status_number_or_owner_fields(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($this->makeUser('manager'))->put("/admin/members/{$member->id}", $this->updateData($member, [
            'status' => 'approved',
            'membership_number' => 'HACK-0001',
            'user_id' => 1,
        ]));

        $fresh = $member->fresh();
        $this->assertSame(Member::STATUS_PENDING, $fresh->status);
        $this->assertNull($fresh->membership_number);
        $this->assertNull($fresh->user_id);
    }

    // ---------- Deactivate / restore ----------

    public function test_only_users_with_deactivate_permission_can_deactivate(): void
    {
        $member = Member::factory()->approved()->create();

        $this->actingAs($this->makeUser('manager'))->post("/admin/members/{$member->id}/deactivate")->assertForbidden();
        $this->actingAs($this->makeUser('coordinator'))->post("/admin/members/{$member->id}/deactivate")->assertForbidden();
        $this->assertSame(Member::STATUS_APPROVED, $member->fresh()->status);
    }

    public function test_deactivate_and_restore_update_status_reviewer_and_login(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $member = Member::factory()->approved()->create();

        $account = new User();
        $account->forceFill(['name' => 'Member Login', 'email' => 'login@example.test', 'password' => 'Password123!', 'is_active' => true])->save();
        $member->forceFill(['user_id' => $account->id])->save();

        $this->actingAs($admin)->post("/admin/members/{$member->id}/deactivate", ['note' => 'Moved abroad'])
            ->assertRedirect("/admin/members/{$member->id}")->assertSessionHas('success');

        $fresh = $member->fresh();
        $this->assertSame(Member::STATUS_DEACTIVATED, $fresh->status);
        $this->assertSame('Moved abroad', $fresh->status_note);
        $this->assertSame($admin->id, $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertFalse($account->fresh()->is_active);

        $this->actingAs($admin)->get("/admin/members/{$member->id}")->assertSee('Restore member')->assertSee('Moved abroad');

        $this->actingAs($admin)->post("/admin/members/{$member->id}/restore")->assertSessionHas('success');

        $this->assertSame(Member::STATUS_APPROVED, $member->fresh()->status);
        $this->assertTrue($account->fresh()->is_active);
    }

    public function test_invalid_transitions_are_refused(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $pending = Member::factory()->create();
        $approved = Member::factory()->approved()->create();

        $this->actingAs($admin)->from("/admin/members/{$pending->id}")
            ->post("/admin/members/{$pending->id}/deactivate")
            ->assertRedirect("/admin/members/{$pending->id}")->assertSessionHasErrors('status');
        $this->assertSame(Member::STATUS_PENDING, $pending->fresh()->status);

        $this->actingAs($admin)->post("/admin/members/{$approved->id}/restore")->assertSessionHasErrors('status');
        $this->assertSame(Member::STATUS_APPROVED, $approved->fresh()->status);
    }

    public function test_deactivated_member_shows_in_filtered_list(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $member = Member::factory()->approved()->create(['full_name' => 'Gone Person']);

        $this->actingAs($admin)->post("/admin/members/{$member->id}/deactivate");

        $this->actingAs($admin)->get('/admin/members?status=deactivated')->assertSee('Gone Person');
        $this->actingAs($admin)->get('/admin/members?status=approved')->assertDontSee('Gone Person');
    }
}
