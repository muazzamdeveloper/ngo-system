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

class MemberDocumentsTest extends TestCase
{
    use RefreshDatabase;

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

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Asha Verma',
            'father_name' => 'Mohan Verma',
            'date_of_birth' => '1992-04-15',
            'gender' => 'female',
            'blood_group' => 'B+',
            'mobile' => '98765 43210',
            'whatsapp' => '97654 32109',
            'email' => 'asha@example.test',
            'address' => '12 Park Road',
            'city' => 'Pune',
            'district' => 'Pune District',
            'work_area' => 'Kothrud',
            'state' => 'Maharashtra',
            'pincode' => '411001',
        ], $overrides);
    }

    private function allDocuments(): array
    {
        return [
            'aadhaar_front' => UploadedFile::fake()->image('front.jpg', 300, 200),
            'aadhaar_back' => UploadedFile::fake()->image('back.png', 300, 200),
            'payment_screenshot' => UploadedFile::fake()->image('pay.webp', 300, 600),
            'resume' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
        ];
    }

    // ---------- New fields ----------

    public function test_new_fields_are_saved_and_whatsapp_is_cleaned(): void
    {
        $this->actingAs($this->makeUser('manager'))->post('/admin/members', $this->validData())->assertSessionHasNoErrors();

        $member = Member::firstOrFail();
        $this->assertSame('B+', $member->blood_group);
        $this->assertSame('9765432109', $member->whatsapp);
        $this->assertSame('Pune District', $member->district);
        $this->assertSame('Kothrud', $member->work_area);
        $this->assertSame('Pune', $member->city);
        $this->assertSame('admin', $member->source);
    }

    public function test_new_fields_are_optional_but_checked_when_given(): void
    {
        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->post('/admin/members', $this->validData([
            'blood_group' => '', 'whatsapp' => '', 'district' => '', 'work_area' => '',
        ]))->assertSessionHasNoErrors();

        $this->actingAs($manager)->post('/admin/members', $this->validData(['email' => 'b@example.test', 'blood_group' => 'Z+']))
            ->assertSessionHasErrors('blood_group');
        $this->actingAs($manager)->post('/admin/members', $this->validData(['email' => 'c@example.test', 'whatsapp' => '123']))
            ->assertSessionHasErrors('whatsapp');

        $this->assertSame(1, Member::count());
    }

    public function test_form_shows_the_new_fields(): void
    {
        $this->actingAs($this->makeUser('manager'))->get('/admin/members/create')
            ->assertOk()->assertSee('Blood group')->assertSee('WhatsApp number')->assertSee('District')
            ->assertSee('Work area')->assertSee('Aadhaar front')->assertSee('Resume');
    }

    // ---------- Uploading ----------

    public function test_documents_are_stored_privately_and_recorded(): void
    {
        $this->actingAs($this->makeUser('manager'))
            ->post('/admin/members', $this->validData($this->allDocuments()))->assertSessionHasNoErrors();

        $member = Member::firstOrFail();

        foreach (Member::DOCUMENT_COLUMNS as $column) {
            $this->assertNotNull($member->{$column}, "{$column} should be set");
            $this->assertStringStartsWith('members/documents/', $member->{$column});
            Storage::disk('local')->assertExists($member->{$column});
        }
    }

    public function test_bad_documents_are_rejected_and_nothing_is_left_behind(): void
    {
        $manager = $this->makeUser('manager');

        $bad = [
            ['aadhaar_front' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain')],
            ['aadhaar_back' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')],
            ['payment_screenshot' => UploadedFile::fake()->image('huge.jpg', 100, 100)->size(6000)],
            ['resume' => UploadedFile::fake()->create('cv.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')],
            ['resume' => UploadedFile::fake()->create('cv.pdf', 11000, 'application/pdf')],
            ['resume' => UploadedFile::fake()->image('cv.jpg', 100, 100)],
        ];

        foreach ($bad as $i => $override) {
            $this->actingAs($manager)->post('/admin/members', $this->validData($override + ['email' => "x{$i}@example.test"]))
                ->assertSessionHasErrors(array_key_first($override));
        }

        $this->assertSame(0, Member::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    // ---------- Viewing ----------

    public function test_document_route_follows_permissions(): void
    {
        $manager = $this->makeUser('manager');
        $this->actingAs($manager)->post('/admin/members', $this->validData($this->allDocuments()));
        $member = Member::firstOrFail();

        // Everyone who may view members sees the payment screenshot and the resume.
        $this->actingAs($manager)->get("/admin/members/{$member->id}/documents/payment-screenshot")->assertOk();
        $this->actingAs($manager)->get("/admin/members/{$member->id}/documents/resume")->assertOk();

        // The Aadhaar images need the Aadhaar permission (super admin only by default).
        $this->actingAs($manager)->get("/admin/members/{$member->id}/documents/aadhaar-front")->assertForbidden();
        $this->actingAs($manager)->get("/admin/members/{$member->id}/documents/aadhaar-back")->assertForbidden();

        $admin = $this->makeUser('super-admin', true);
        $this->actingAs($admin)->get("/admin/members/{$member->id}/documents/aadhaar-front")->assertOk();
    }

    public function test_aadhaar_document_views_are_logged_and_not_cached(): void
    {
        Log::spy();
        $this->actingAs($this->makeUser('manager'))->post('/admin/members', $this->validData($this->allDocuments()));
        $member = Member::firstOrFail();
        $admin = $this->makeUser('super-admin', true);

        $response = $this->actingAs($admin)->get("/admin/members/{$member->id}/documents/aadhaar-back");

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));

        Log::shouldHaveReceived('info')->withArgs(
            fn ($message, $context) => $message === 'Aadhaar document viewed'
                && $context['member_id'] === $member->id
                && $context['document'] === 'aadhaar_back'
                && $context['user_id'] === $admin->id
        )->once();
    }

    public function test_other_users_and_guests_cannot_open_documents(): void
    {
        $this->actingAs($this->makeUser('manager'))->post('/admin/members', $this->validData($this->allDocuments()));
        $member = Member::firstOrFail();

        $this->actingAs($this->makeUser('member'))->get("/admin/members/{$member->id}/documents/payment-screenshot")->assertForbidden();

        auth()->logout();
        $this->get("/admin/members/{$member->id}/documents/payment-screenshot")->assertRedirect('/login');
    }

    public function test_unknown_or_missing_documents_give_404(): void
    {
        $admin = $this->makeUser('super-admin', true);
        $member = Member::factory()->create();

        $this->actingAs($admin)->get("/admin/members/{$member->id}/documents/resume")->assertNotFound();
        $this->actingAs($admin)->get("/admin/members/{$member->id}/documents/aadhaar-front")->assertNotFound();
        $this->actingAs($admin)->get("/admin/members/{$member->id}/documents/secrets")->assertNotFound();
        $this->actingAs($admin)->get("/admin/members/{$member->id}/documents/..%2F..%2F.env")->assertNotFound();
    }

    public function test_profile_lists_documents_and_hides_aadhaar_links_from_managers(): void
    {
        $manager = $this->makeUser('manager');
        $this->actingAs($manager)->post('/admin/members', $this->validData($this->allDocuments()));
        $member = Member::firstOrFail();

        $response = $this->actingAs($manager)->get("/admin/members/{$member->id}");
        $response->assertOk()->assertSee('Documents')
            ->assertSee("/documents/payment-screenshot", false)
            ->assertSee("/documents/resume", false)
            ->assertDontSee("/documents/aadhaar-front", false)
            ->assertSee('Uploaded (restricted)')
            ->assertSee('Blood group')->assertSee('B+')->assertSee('Kothrud')->assertSee('Added by staff');

        $this->actingAs($this->makeUser('super-admin', true))->get("/admin/members/{$member->id}")
            ->assertSee("/documents/aadhaar-front", false)->assertSee("/documents/aadhaar-back", false);
    }

    // ---------- Replacing and removing ----------

    public function test_documents_can_be_replaced_and_removed_and_old_files_are_deleted(): void
    {
        $manager = $this->makeUser('manager');
        $this->actingAs($manager)->post('/admin/members', $this->validData($this->allDocuments()));
        $member = Member::firstOrFail();

        $oldFront = $member->aadhaar_front_path;
        $oldResume = $member->resume_path;
        $keptBack = $member->aadhaar_back_path;

        $update = $this->validData([
            'aadhaar_front' => UploadedFile::fake()->image('new-front.jpg', 300, 200),
            'remove_resume' => '1',
        ]);

        $this->actingAs($manager)->put("/admin/members/{$member->id}", $update)->assertSessionHasNoErrors();

        $fresh = $member->fresh();
        $this->assertNotSame($oldFront, $fresh->aadhaar_front_path);
        Storage::disk('local')->assertMissing($oldFront);
        Storage::disk('local')->assertExists($fresh->aadhaar_front_path);

        $this->assertNull($fresh->resume_path);
        Storage::disk('local')->assertMissing($oldResume);

        // Untouched documents stay.
        $this->assertSame($keptBack, $fresh->aadhaar_back_path);
        Storage::disk('local')->assertExists($keptBack);
    }

    public function test_updating_without_files_keeps_existing_documents(): void
    {
        $manager = $this->makeUser('manager');
        $this->actingAs($manager)->post('/admin/members', $this->validData($this->allDocuments()));
        $member = Member::firstOrFail();
        $before = $member->only(array_values(Member::DOCUMENT_COLUMNS));

        $this->actingAs($manager)->put("/admin/members/{$member->id}", $this->validData(['full_name' => 'Asha V']))
            ->assertSessionHasNoErrors();

        $this->assertSame($before, $member->fresh()->only(array_values(Member::DOCUMENT_COLUMNS)));
        $this->assertSame('Asha V', $member->fresh()->full_name);
    }

    public function test_document_paths_cannot_be_set_from_the_form(): void
    {
        $this->actingAs($this->makeUser('manager'))->post('/admin/members', $this->validData([
            'aadhaar_front_path' => '../../.env',
            'resume_path' => 'etc/passwd',
            'source' => 'online',
        ]));

        $member = Member::firstOrFail();
        $this->assertNull($member->aadhaar_front_path);
        $this->assertNull($member->resume_path);
        $this->assertSame('admin', $member->source);
    }

    public function test_edit_form_shows_current_files_and_remove_options(): void
    {
        $manager = $this->makeUser('manager');
        $this->actingAs($manager)->post('/admin/members', $this->validData($this->allDocuments()));
        $member = Member::firstOrFail();

        $this->actingAs($manager)->get("/admin/members/{$member->id}/edit")
            ->assertOk()->assertSee('Remove current file')->assertSee('Replace resume')
            ->assertSee('/documents/resume', false)->assertDontSee('/documents/aadhaar-front', false);
    }
}
