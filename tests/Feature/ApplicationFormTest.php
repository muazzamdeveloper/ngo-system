<?php

namespace Tests\Feature;

use App\Http\Requests\ApplyRequest;
use App\Models\CardSetting;
use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationFormTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_AADHAAR = '234567890124';
    private const OTHER_VALID_AADHAAR = '298765432101';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Asha',
            'last_name' => 'Verma',
            'gender' => 'female',
            'father_name' => 'Mohan Verma',
            'date_of_birth' => '1992-04-15',
            'blood_group' => 'B+',
            'mobile' => '9876543210',
            'whatsapp' => '9765432109',
            'email' => 'asha@example.test',
            'aadhaar' => self::VALID_AADHAAR,
            'state' => 'Maharashtra',
            'district' => 'Pune',
            'city' => 'Pune City',
            'work_area' => 'Kothrud',
            'pincode' => '411001',
            'address' => '12 Park Road',
        ], $overrides);
    }

    private function files(array $overrides = []): array
    {
        return array_merge([
            'photo' => UploadedFile::fake()->image('me.jpg', 300, 300),
            'aadhaar_front' => UploadedFile::fake()->image('front.jpg', 300, 200),
            'aadhaar_back' => UploadedFile::fake()->image('back.png', 300, 200),
        ], $overrides);
    }

    /** Sends the form. The hourly limit is reset first so tests that send many forms are not blocked. */
    private function submit(array $overrides = [], array $fileOverrides = [])
    {
        RateLimiter::clear(md5('apply' . '127.0.0.1'));

        return $this->post('/apply', array_merge($this->payload(), $this->files(), $overrides, $fileOverrides));
    }

    private function enablePaymentQr(?string $fee = null): void
    {
        $path = UploadedFile::fake()->image('qr.png', 300, 300)->store('card-settings', 'local');
        CardSetting::query()->firstOrFail()->forceFill(['payment_qr_path' => $path, 'fee_amount' => $fee])->save();
    }

    private function existingMember(array $overrides = [], ?string $aadhaar = self::VALID_AADHAAR): Member
    {
        $member = new Member();
        $member->forceFill(array_merge([
            'full_name' => 'Existing Person',
            'father_name' => 'Father',
            'date_of_birth' => '1980-01-01',
            'gender' => 'male',
            'mobile' => '9000000000',
            'email' => 'existing@example.test',
            'address' => 'Somewhere',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'status' => Member::STATUS_APPROVED,
        ], $overrides));
        $member->setAadhaar($aadhaar)->save();

        return $member;
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

    // ---------- The page ----------

    public function test_page_is_open_to_everyone_and_shows_the_form(): void
    {
        $this->get('/apply')->assertOk()
            ->assertSee('Application Form')
            ->assertSee('name="first_name"', false)
            ->assertSee('name="aadhaar"', false)
            ->assertSee('name="aadhaar_front"', false)
            ->assertSee('name="payment_screenshot"', false)
            ->assertSee('name="photo"', false)
            ->assertSee('name="resume"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="website"', false)            // the bot trap
            ->assertDontSee('Payment / Application fee')    // nothing configured yet
            ->assertDontSee('name="designation"', false);
    }

    public function test_logged_in_staff_can_also_open_the_page(): void
    {
        $this->actingAs($this->makeUser('manager'))->get('/apply')->assertOk();
    }

    public function test_qr_and_fee_text_show_when_both_are_set(): void
    {
        $this->enablePaymentQr('500');

        $this->get('/apply')->assertOk()
            ->assertSee('Payment / Application Fee')
            ->assertSee('Application fee:')->assertSee('<strong>₹500</strong>', false)
            ->assertSee('/payment-qr', false);
    }

    public function test_qr_shows_without_fee_text_when_no_amount_is_set(): void
    {
        $this->enablePaymentQr(null);

        $this->get('/apply')->assertOk()
            ->assertSee('/payment-qr', false)
            ->assertDontSee('Application fee:');
    }

    public function test_fee_text_without_a_qr_shows_no_qr_image(): void
    {
        CardSetting::query()->firstOrFail()->forceFill(['fee_amount' => '250'])->save();

        $this->get('/apply')->assertOk()
            ->assertSee('Application fee:')->assertSee('<strong>₹250</strong>', false)
            ->assertDontSee('/payment-qr', false);
    }

    public function test_the_qr_image_itself_is_served(): void
    {
        $this->enablePaymentQr();

        $this->get('/payment-qr')->assertOk();
    }

    // ---------- Sending a valid application ----------

    public function test_valid_application_is_saved_as_a_pending_online_member(): void
    {
        $this->submit()->assertSessionHasNoErrors()->assertRedirect('/apply/thanks');

        $member = Member::firstOrFail();
        $this->assertSame(1, Member::count());
        $this->assertSame('Asha Verma', $member->full_name);
        $this->assertSame('Mohan Verma', $member->father_name);
        $this->assertSame('female', $member->gender);
        $this->assertSame('B+', $member->blood_group);
        $this->assertSame('9876543210', $member->mobile);
        $this->assertSame('9765432109', $member->whatsapp);
        $this->assertSame('asha@example.test', $member->email);
        $this->assertSame('Maharashtra', $member->state);
        $this->assertSame('Pune', $member->district);
        $this->assertSame('Pune', $member->city);   // the form has no City box: the district is used
        $this->assertSame('Kothrud', $member->work_area);
        $this->assertSame('411001', $member->pincode);
        $this->assertSame(Member::STATUS_PENDING, $member->status);
        $this->assertSame(Member::SOURCE_ONLINE, $member->source);
        $this->assertNull($member->created_by);
        $this->assertNull($member->user_id);
        $this->assertNull($member->membership_number);
        $this->assertNull($member->approved_at);
    }

    public function test_aadhaar_is_stored_encrypted_and_documents_privately(): void
    {
        $this->submit();

        $member = Member::firstOrFail();
        $this->assertSame(self::VALID_AADHAAR, $member->fullAadhaar());
        $this->assertSame('0124', $member->aadhaar_last4);
        $this->assertNotNull($member->aadhaar_hash);
        $this->assertStringNotContainsString(self::VALID_AADHAAR, (string) $member->aadhaar_encrypted);
        $this->assertNotNull(Member::findByAadhaar(self::VALID_AADHAAR));

        foreach (['photo_path', 'aadhaar_front_path', 'aadhaar_back_path'] as $column) {
            $this->assertStringStartsWith('members/', $member->{$column});
            Storage::disk('local')->assertExists($member->{$column});
        }

        $this->assertNull($member->payment_screenshot_path);
        $this->assertNull($member->resume_path);
    }

    public function test_numbers_with_spaces_and_dashes_are_cleaned(): void
    {
        $this->submit(['aadhaar' => '2345 6789 0124', 'mobile' => '98765 43210', 'whatsapp' => '97654-32109', 'email' => '  Asha@Example.TEST ']);

        $member = Member::firstOrFail();
        $this->assertSame(self::VALID_AADHAAR, $member->fullAadhaar());
        $this->assertSame('9876543210', $member->mobile);
        $this->assertSame('9765432109', $member->whatsapp);
        $this->assertSame('asha@example.test', $member->email);
    }

    public function test_names_are_trimmed_and_joined(): void
    {
        $this->submit(['first_name' => '  Asha   ', 'last_name' => ' Devi  Verma ']);

        $this->assertSame('Asha Devi Verma', Member::firstOrFail()->full_name);
    }

    public function test_optional_fields_can_be_left_out(): void
    {
        $this->submit(['whatsapp' => null])->assertSessionHasNoErrors()->assertRedirect('/apply/thanks');

        $this->assertNull(Member::firstOrFail()->whatsapp);
    }

    public function test_resume_and_payment_screenshot_are_saved_when_given(): void
    {
        $this->enablePaymentQr();

        $this->submit([], [
            'resume' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
            'payment_screenshot' => UploadedFile::fake()->image('pay.webp', 300, 600),
        ])->assertSessionHasNoErrors();

        $member = Member::firstOrFail();
        Storage::disk('local')->assertExists($member->resume_path);
        Storage::disk('local')->assertExists($member->payment_screenshot_path);
    }

    public function test_thanks_page_shows_once_and_then_goes_back_to_the_form(): void
    {
        $this->get('/apply/thanks')->assertRedirect('/apply');

        $this->submit();
        $this->followingRedirects()->get('/apply/thanks')->assertOk();

        $this->get('/apply/thanks')->assertRedirect('/apply');
    }

    public function test_thanks_page_does_not_reveal_member_details(): void
    {
        $this->submit();

        $this->get('/apply/thanks')->assertOk()
            ->assertSee('Application received')
            ->assertDontSee('Asha')
            ->assertDontSee(self::VALID_AADHAAR);
    }

    // ---------- Required fields ----------

    public function test_every_required_field_is_enforced_and_nothing_is_saved(): void
    {
        $required = [
            'first_name', 'last_name', 'gender', 'date_of_birth', 'blood_group', 'mobile', 'email',
            'aadhaar', 'state', 'district', 'work_area', 'address',
        ];

        foreach ($required as $field) {
            $this->submit([$field => ''])->assertSessionHasErrors($field);
        }

        foreach (['photo', 'aadhaar_front', 'aadhaar_back'] as $field) {
            RateLimiter::clear(md5('apply' . '127.0.0.1'));
            $data = array_merge($this->payload(), $this->files());
            unset($data[$field]);
            $this->post('/apply', $data)->assertSessionHasErrors($field);
        }

        $this->assertSame(0, Member::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_payment_screenshot_is_needed_only_when_a_qr_is_set(): void
    {
        $this->submit()->assertSessionHasNoErrors();   // no QR: not needed

        Member::query()->delete();
        $this->enablePaymentQr('500');

        $this->submit(['aadhaar' => self::OTHER_VALID_AADHAAR, 'email' => 'other@example.test'])
            ->assertSessionHasErrors('payment_screenshot');

        $this->submit(['aadhaar' => self::OTHER_VALID_AADHAAR, 'email' => 'other@example.test'], [
            'payment_screenshot' => UploadedFile::fake()->image('pay.jpg', 300, 600),
        ])->assertSessionHasNoErrors();
    }

    // ---------- Bad values ----------

    public function test_bad_values_are_rejected(): void
    {
        $bad = [
            ['aadhaar' => '23456789012'],          // 11 digits
            ['aadhaar' => '2345678901245'],        // 13 digits
            ['aadhaar' => '23456789012A'],         // letters
            ['mobile' => '12345'],
            ['mobile' => 'abcdefghij'],
            ['whatsapp' => '12345'],
            ['pincode' => '4110'],
            ['pincode' => '4110011'],
            ['date_of_birth' => now()->addDay()->format('Y-m-d')],
            ['date_of_birth' => now()->format('Y-m-d')],
            ['date_of_birth' => '1899-12-31'],
            ['date_of_birth' => 'not-a-date'],
            ['gender' => 'x'],
            ['blood_group' => 'Z+'],
            ['state' => 'Atlantis'],
            ['email' => 'not-an-email'],
            ['first_name' => str_repeat('a', 101)],
            ['last_name' => str_repeat('a', 101)],
            ['address' => str_repeat('a', 501)],
        ];

        foreach ($bad as $override) {
            $this->submit($override)->assertSessionHasErrors(array_key_first($override));
        }

        $this->assertSame(0, Member::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_aadhaar_is_not_copied_into_the_session_after_a_failed_form(): void
    {
        $this->submit(['email' => 'broken'])
            ->assertSessionHasErrors('email')
            ->assertSessionHas('_old_input.first_name', 'Asha')
            ->assertSessionMissing('_old_input.aadhaar');
    }

    public function test_script_in_text_fields_is_stored_as_text_and_shown_escaped_to_staff(): void
    {
        $this->submit(['first_name' => '<script>alert(1)</script>', 'last_name' => 'X'])->assertSessionHasNoErrors();

        $manager = $this->makeUser('manager');
        $this->actingAs($manager)->get('/admin/members')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    // ---------- Bad files ----------

    public function test_bad_files_are_rejected_and_nothing_is_left_behind(): void
    {
        $bad = [
            ['photo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain')],
            ['photo' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')],
            ['photo' => UploadedFile::fake()->create('me.svg', 10, 'image/svg+xml')],
            ['photo' => UploadedFile::fake()->image('big.jpg', 100, 100)->size(3000)],
            ['aadhaar_front' => UploadedFile::fake()->create('front.pdf', 10, 'application/pdf')],
            ['aadhaar_back' => UploadedFile::fake()->image('back.gif', 100, 100)],
            ['aadhaar_back' => UploadedFile::fake()->image('huge.jpg', 100, 100)->size(6000)],
            ['resume' => UploadedFile::fake()->create('cv.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')],
            ['resume' => UploadedFile::fake()->create('cv.pdf', 11000, 'application/pdf')],
            ['resume' => UploadedFile::fake()->image('cv.jpg', 100, 100)],
        ];

        foreach ($bad as $files) {
            $this->submit([], $files)->assertSessionHasErrors(array_key_first($files));
        }

        $this->assertSame(0, Member::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    // ---------- Duplicates ----------

    public function test_existing_aadhaar_is_refused_with_a_neutral_message_in_every_status(): void
    {
        foreach ([Member::STATUS_PENDING, Member::STATUS_APPROVED, Member::STATUS_REJECTED, Member::STATUS_BLOCKED, Member::STATUS_DEACTIVATED] as $status) {
            Member::query()->delete();
            $this->existingMember(['status' => $status]);

            $this->submit(['email' => 'new@example.test'])
                ->assertSessionHasErrors(['form' => ApplyRequest::DUPLICATE_MESSAGE]);

            $this->assertSame(1, Member::count(), "status {$status}");
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_existing_email_is_refused_with_the_same_message(): void
    {
        $this->existingMember(['email' => 'asha@example.test'], self::OTHER_VALID_AADHAAR);

        $this->submit()->assertSessionHasErrors(['form' => ApplyRequest::DUPLICATE_MESSAGE]);

        $this->assertSame(1, Member::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_the_message_does_not_say_which_detail_matched(): void
    {
        $this->existingMember();

        $response = $this->submit(['email' => 'new@example.test']);
        $error = session('errors')->first('form');

        $this->assertSame(ApplyRequest::DUPLICATE_MESSAGE, $error);
        $this->assertStringNotContainsStringIgnoringCase('aadhaar', $error);
        $this->assertStringNotContainsStringIgnoringCase('email', $error);

        $this->followingRedirects()->get('/apply')->assertSee('We could not accept this application');
        $response->assertSessionMissing('_old_input.aadhaar');
    }

    public function test_sending_the_same_application_twice_saves_only_one(): void
    {
        $this->submit()->assertRedirect('/apply/thanks');
        $this->submit()->assertSessionHasErrors('form');

        $this->assertSame(1, Member::count());
        $this->assertCount(3, Storage::disk('local')->allFiles());
    }

    public function test_a_different_person_can_apply(): void
    {
        $this->existingMember();

        $this->submit(['aadhaar' => self::OTHER_VALID_AADHAAR, 'email' => 'new@example.test'])
            ->assertSessionHasNoErrors()->assertRedirect('/apply/thanks');

        $this->assertSame(2, Member::count());
    }

    // ---------- Abuse ----------

    public function test_filled_bot_trap_pretends_to_work_but_saves_nothing(): void
    {
        $this->submit(['website' => 'http://spam.example'])->assertRedirect('/apply/thanks');

        $this->assertSame(0, Member::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_fields_that_only_staff_may_set_are_ignored(): void
    {
        $staff = $this->makeUser('manager');

        $this->submit([
            'status' => 'approved',
            'membership_number' => 'LM-2026-9999',
            'user_id' => $staff->id,
            'created_by' => $staff->id,
            'reviewed_by' => $staff->id,
            'approved_at' => now()->toDateTimeString(),
            'source' => 'admin',
            'photo_path' => '../../.env',
            'aadhaar_hash' => 'x',
            'aadhaar_last4' => '0000',
        ])->assertSessionHasNoErrors();

        $member = Member::firstOrFail();
        $this->assertSame(Member::STATUS_PENDING, $member->status);
        $this->assertNull($member->membership_number);
        $this->assertNull($member->user_id);
        $this->assertNull($member->created_by);
        $this->assertNull($member->reviewed_by);
        $this->assertNull($member->approved_at);
        $this->assertSame(Member::SOURCE_ONLINE, $member->source);
        $this->assertStringStartsWith('members/photos/', $member->photo_path);
        $this->assertSame('0124', $member->aadhaar_last4);
    }

    public function test_sending_is_limited_to_ten_times_an_hour_from_one_address(): void
    {
        RateLimiter::clear(md5('apply' . '127.0.0.1'));

        for ($i = 0; $i < 10; $i++) {
            $this->post('/apply', [])->assertSessionHasErrors();
        }

        $this->post('/apply', [])->assertStatus(429);
        $this->post('/apply', array_merge($this->payload(), $this->files()))->assertStatus(429);
        $this->assertSame(0, Member::count());

        // Opening the form is never limited.
        $this->get('/apply')->assertOk();
    }

    // ---------- What staff see ----------

    public function test_staff_see_the_application_and_its_documents(): void
    {
        $this->enablePaymentQr();
        $this->submit([], ['payment_screenshot' => UploadedFile::fake()->image('pay.jpg', 300, 600)]);
        $member = Member::firstOrFail();

        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->get('/admin/members')->assertOk()->assertSee('Asha Verma')->assertSee('Pending');
        $this->actingAs($manager)->get("/admin/members/{$member->id}")->assertOk()
            ->assertSee('Asha Verma')->assertSee('Payment screenshot');
        $this->actingAs($manager)->get("/admin/members/{$member->id}/documents/payment-screenshot")->assertOk();
        $this->actingAs($manager)->get("/admin/members/{$member->id}/photo")->assertOk();

        // Aadhaar images stay behind the Aadhaar permission.
        $this->actingAs($manager)->get("/admin/members/{$member->id}/documents/aadhaar-front")->assertForbidden();
        $this->actingAs($this->makeUser('super-admin', true))->get("/admin/members/{$member->id}/documents/aadhaar-front")->assertOk();
    }

    public function test_an_application_can_be_approved_like_any_other_member(): void
    {
        $this->submit();
        $member = Member::firstOrFail();

        $this->actingAs($this->makeUser('manager'))->post("/admin/members/{$member->id}/approve")
            ->assertSessionHasNoErrors();

        $member->refresh();
        $this->assertSame(Member::STATUS_APPROVED, $member->status);
        $this->assertNotNull($member->membership_number);
        $this->assertNotNull($member->approved_at);
    }

    public function test_guests_cannot_open_uploaded_files(): void
    {
        $this->submit();
        $member = Member::firstOrFail();

        $this->get("/admin/members/{$member->id}/photo")->assertRedirect('/login');
        $this->get("/admin/members/{$member->id}/documents/aadhaar-front")->assertRedirect('/login');
        // Laravel answers an unsigned /storage link with 403 (or 404 when no link exists): never the file.
        $this->assertContains($this->get('/storage/' . $member->photo_path)->getStatusCode(), [403, 404]);
    }
}
