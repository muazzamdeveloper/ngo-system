<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberCreateTest extends TestCase
{
    use RefreshDatabase;

    // Synthetic numbers that pass the Aadhaar check-digit test.
    private const VALID_AADHAAR = '234567890124';
    private const OTHER_VALID_AADHAAR = '298765432101';

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
            'mobile' => '98765 43210',
            'email' => 'asha@example.test',
            'address' => '12 Park Road',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pincode' => '411001',
            'occupation' => 'Teacher',
        ], $overrides);
    }

    public function test_only_users_with_create_permission_can_see_form_and_submit(): void
    {
        $this->actingAs($this->makeUser('manager'))->get('/admin/members/create')->assertOk()->assertSee('Save member');

        $coordinator = $this->makeUser('coordinator');
        $this->actingAs($coordinator)->get('/admin/members/create')->assertForbidden();
        $this->actingAs($coordinator)->post('/admin/members', $this->validData())->assertForbidden();
        $this->assertSame(0, Member::count());

        auth()->logout();
        $this->post('/admin/members', $this->validData())->assertRedirect('/login');
    }

    public function test_add_button_is_shown_only_with_permission(): void
    {
        $this->actingAs($this->makeUser('manager'))->get('/admin/members')->assertSee('Add member');
        $this->actingAs($this->makeUser('coordinator'))->get('/admin/members')->assertDontSee('+ Add member');
    }

    public function test_manager_can_add_a_member_with_photo_and_aadhaar(): void
    {
        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->post('/admin/members', $this->validData([
            'aadhaar' => '2345 6789 0124',
            'photo' => UploadedFile::fake()->image('photo.jpg', 300, 300),
        ]))->assertRedirect('/admin/members')->assertSessionHas('success');

        $member = Member::firstOrFail();
        $this->assertSame('Asha Verma', $member->full_name);
        $this->assertSame('9876543210', $member->mobile);
        $this->assertSame(Member::STATUS_PENDING, $member->status);
        $this->assertNull($member->membership_number);
        $this->assertSame($manager->id, $member->created_by);
        $this->assertSame(self::VALID_AADHAAR, $member->fullAadhaar());
        $this->assertSame('XXXX XXXX 0124', $member->maskedAadhaar());

        $raw = DB::table('members')->where('id', $member->id)->first();
        $this->assertStringNotContainsString(self::VALID_AADHAAR, json_encode($raw));

        Storage::disk('local')->assertExists($member->photo_path);
        $this->assertStringStartsWith('members/photos/', $member->photo_path);
    }

    public function test_aadhaar_and_photo_are_optional(): void
    {
        $this->actingAs($this->makeUser('manager'))->post('/admin/members', $this->validData())->assertSessionHasNoErrors();

        $member = Member::firstOrFail();
        $this->assertNull($member->photo_path);
        $this->assertNull($member->aadhaar_hash);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs($this->makeUser('manager'))->post('/admin/members', [])
            ->assertSessionHasErrors([
                'full_name', 'father_name', 'date_of_birth', 'gender', 'mobile', 'address', 'city', 'state', 'pincode',
            ]);

        $this->assertSame(0, Member::count());
    }

    public function test_bad_values_are_rejected(): void
    {
        $manager = $this->makeUser('manager');

        foreach ([
            ['pincode' => '12345'],
            ['pincode' => 'ABCDEF'],
            ['mobile' => '12345'],
            ['mobile' => 'call-me-maybe'],
            ['date_of_birth' => now()->addDay()->toDateString()],
            ['gender' => 'unknown'],
            ['email' => 'not-an-email'],
        ] as $override) {
            $this->actingAs($manager)->post('/admin/members', $this->validData($override))
                ->assertSessionHasErrors(array_key_first($override));
        }

        $this->assertSame(0, Member::count());
    }

    public function test_duplicate_email_is_rejected(): void
    {
        Member::factory()->create(['email' => 'asha@example.test']);

        $this->actingAs($this->makeUser('manager'))->post('/admin/members', $this->validData())
            ->assertSessionHasErrors('email');
    }

    public function test_aadhaar_validation(): void
    {
        $manager = $this->makeUser('manager');

        foreach (['12345', 'abcdefghijkl', '2345678901234'] as $bad) {
            $this->actingAs($manager)->post('/admin/members', $this->validData(['aadhaar' => $bad]))
                ->assertSessionHasErrors('aadhaar');
        }

        $this->assertSame(0, Member::count());

        $this->actingAs($manager)->post('/admin/members', $this->validData(['aadhaar' => self::VALID_AADHAAR]))
            ->assertSessionHasNoErrors();

        // Same number again (even with different formatting) is a duplicate.
        $this->actingAs($manager)->post('/admin/members', $this->validData([
            'email' => 'other@example.test',
            'aadhaar' => '2345-6789-0124',
        ]))->assertSessionHasErrors('aadhaar');

        $this->assertSame(1, Member::count());
    }

    public function test_aadhaar_checksum_can_be_switched_off_in_config(): void
    {
        config(['ngo.aadhaar_checksum' => false]);

        $this->actingAs($this->makeUser('manager'))
            ->post('/admin/members', $this->validData(['aadhaar' => '234567890125']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Member::count());
    }

    public function test_bad_photos_are_rejected(): void
    {
        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->post('/admin/members', $this->validData([
            'photo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ]))->assertSessionHasErrors('photo');

        $this->actingAs($manager)->post('/admin/members', $this->validData([
            'photo' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ]))->assertSessionHasErrors('photo');

        $this->actingAs($manager)->post('/admin/members', $this->validData([
            'photo' => UploadedFile::fake()->image('huge.jpg', 100, 100)->size(3000),
        ]))->assertSessionHasErrors('photo');

        $this->assertSame(0, Member::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_status_and_number_cannot_be_injected_through_the_form(): void
    {
        $this->actingAs($this->makeUser('manager'))->post('/admin/members', $this->validData([
            'status' => 'approved',
            'membership_number' => 'HACK-0001',
            'user_id' => 1,
            'created_by' => 999,
        ]));

        $member = Member::firstOrFail();
        $this->assertSame(Member::STATUS_PENDING, $member->status);
        $this->assertNull($member->membership_number);
        $this->assertNull($member->user_id);
        $this->assertNotSame(999, $member->created_by);
    }

    public function test_old_input_is_kept_after_a_validation_error(): void
    {
        $this->actingAs($this->makeUser('manager'))
            ->from('/admin/members/create')
            ->post('/admin/members', $this->validData(['pincode' => '1']))
            ->assertRedirect('/admin/members/create');

        $this->get('/admin/members/create')->assertSee('Asha Verma');
    }

    public function test_photo_is_served_only_through_a_permission_checked_route(): void
    {
        $manager = $this->makeUser('manager');
        $this->actingAs($manager)->post('/admin/members', $this->validData([
            'photo' => UploadedFile::fake()->image('photo.jpg', 200, 200),
        ]));
        $member = Member::firstOrFail();

        $this->actingAs($manager)->get("/admin/members/{$member->id}/photo")->assertOk();
        $this->actingAs($this->makeUser('coordinator'))->get("/admin/members/{$member->id}/photo")->assertOk();
        $this->actingAs($this->makeUser('member'))->get("/admin/members/{$member->id}/photo")->assertForbidden();

        auth()->logout();
        $this->get("/admin/members/{$member->id}/photo")->assertRedirect('/login');

        $noPhoto = Member::factory()->create();
        $this->actingAs($manager)->get("/admin/members/{$noPhoto->id}/photo")->assertNotFound();
    }
}
