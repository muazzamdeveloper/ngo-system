<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    /** A member with a login, as created by staff and after the first password change. */
    private function memberWithLogin(string $email, string $name, array $memberAttributes = []): array
    {
        $user = new User();
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => 'Password123!',
            'is_active' => true,
            'role_id' => Role::where('name', 'member')->value('id'),
        ])->save();

        $member = Member::factory()->approved()->withAadhaar()->create(array_merge(
            ['full_name' => $name, 'email' => $email], $memberAttributes
        ));
        $member->forceFill(['user_id' => $user->id, 'membership_number' => 'LM-2026-' . str_pad((string) $member->id, 4, '0', STR_PAD_LEFT)])->save();

        return [$user, $member->fresh()];
    }

    private function validUpdate(array $overrides = []): array
    {
        return array_merge([
            'mobile' => '98111 22233',
            'address' => '5 New Street',
            'city' => 'Jaipur',
            'state' => 'Rajasthan',
            'pincode' => '302001',
            'occupation' => 'Designer',
        ], $overrides);
    }

    // ---------- Viewing ----------

    public function test_member_sees_their_own_profile_with_masked_aadhaar(): void
    {
        [$user, $member] = $this->memberWithLogin('asha@example.test', 'Asha Verma');
        $member->setAadhaar('234567890124')->save();
        [, $other] = $this->memberWithLogin('ravi@example.test', 'Ravi Kumar');

        $this->actingAs($user)->get('/account')
            ->assertOk()
            ->assertSee('Asha Verma')
            ->assertSee($member->membership_number)
            ->assertSee('XXXX XXXX 0124')
            ->assertDontSee('234567890124')
            ->assertDontSee('Ravi Kumar')
            ->assertDontSee($other->membership_number)
            ->assertSee('Download ID card')->assertSee('Download joining letter');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/account')->assertRedirect('/login');
        $this->get('/account/profile')->assertRedirect('/login');
        $this->put('/account/profile', $this->validUpdate())->assertRedirect('/login');
        $this->get('/account/photo')->assertRedirect('/login');
    }

    public function test_user_without_a_member_record_gets_a_message_and_404s(): void
    {
        $staff = new User();
        $staff->forceFill(['name' => 'Staff', 'email' => 's@example.test', 'password' => 'Password123!', 'is_active' => true,
            'role_id' => Role::where('name', 'member')->value('id')])->save();   // a Member-role login with no membership record

        $this->actingAs($staff)->get('/account')->assertOk()->assertSee('not linked to a membership record');
        $this->actingAs($staff)->get('/account/profile')->assertNotFound();
        $this->actingAs($staff)->put('/account/profile', $this->validUpdate())->assertNotFound();
        $this->actingAs($staff)->get('/account/photo')->assertNotFound();
    }

    // ---------- Editing ----------

    public function test_member_can_update_the_allowed_fields(): void
    {
        [$user, $member] = $this->memberWithLogin('asha@example.test', 'Asha Verma');

        $this->actingAs($user)->get('/account/profile')->assertOk()->assertSee($member->mobile);

        $this->actingAs($user)->put('/account/profile', $this->validUpdate())
            ->assertRedirect('/account')->assertSessionHas('success');

        $fresh = $member->fresh();
        $this->assertSame('9811122233', $fresh->mobile);
        $this->assertSame('5 New Street', $fresh->address);
        $this->assertSame('Jaipur', $fresh->city);
        $this->assertSame('Rajasthan', $fresh->state);
        $this->assertSame('302001', $fresh->pincode);
        $this->assertSame('Designer', $fresh->occupation);
    }

    public function test_member_cannot_change_protected_fields(): void
    {
        [$user, $member] = $this->memberWithLogin('asha@example.test', 'Asha Verma');
        $before = $member->only(['full_name', 'father_name', 'date_of_birth', 'gender', 'email', 'status', 'membership_number', 'user_id', 'aadhaar_last4']);
        $aadhaarHash = $member->aadhaar_hash;

        $this->actingAs($user)->put('/account/profile', $this->validUpdate([
            'full_name' => 'Hacked Name',
            'father_name' => 'Hacked Father',
            'date_of_birth' => '1980-01-01',
            'gender' => 'other',
            'email' => 'hacked@example.test',
            'status' => 'pending',
            'membership_number' => 'HACK-0001',
            'user_id' => 999,
            'aadhaar' => '298765432101',
        ]));

        $fresh = $member->fresh();
        $this->assertEquals($before, $fresh->only(array_keys($before)));
        $this->assertSame($aadhaarHash, $fresh->aadhaar_hash);
    }

    public function test_updating_never_touches_another_members_record(): void
    {
        [$user] = $this->memberWithLogin('asha@example.test', 'Asha Verma');
        [, $other] = $this->memberWithLogin('ravi@example.test', 'Ravi Kumar', ['city' => 'Nagpur']);

        $this->actingAs($user)->put('/account/profile', $this->validUpdate(['city' => 'Jaipur', 'member_id' => $other->id]));

        $this->assertSame('Nagpur', $other->fresh()->city);
    }

    public function test_bad_values_are_rejected(): void
    {
        [$user, $member] = $this->memberWithLogin('asha@example.test', 'Asha Verma');

        foreach ([
            ['mobile' => '12345'],
            ['mobile' => 'abc'],
            ['pincode' => '1234'],
            ['address' => ''],
            ['city' => ''],
            ['state' => ''],
        ] as $override) {
            $this->actingAs($user)->put('/account/profile', $this->validUpdate($override))
                ->assertSessionHasErrors(array_key_first($override));
        }

        $this->assertSame($member->city, $member->fresh()->city);
    }

    // ---------- Photo ----------

    public function test_photo_can_be_added_replaced_removed_and_viewed_only_by_its_owner(): void
    {
        [$user, $member] = $this->memberWithLogin('asha@example.test', 'Asha Verma');
        [$otherUser, $other] = $this->memberWithLogin('ravi@example.test', 'Ravi Kumar');

        $this->actingAs($user)->put('/account/profile', $this->validUpdate(['photo' => UploadedFile::fake()->image('one.jpg', 200, 200)]));
        $first = $member->fresh()->photo_path;
        Storage::disk('local')->assertExists($first);
        $this->assertStringStartsWith('members/photos/', $first);

        $this->actingAs($user)->get('/account/photo')->assertOk();
        $this->actingAs($user)->get('/account')->assertSee('/account/photo', false);

        $this->actingAs($user)->put('/account/profile', $this->validUpdate(['photo' => UploadedFile::fake()->image('two.jpg', 200, 200)]));
        $second = $member->fresh()->photo_path;
        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);

        // The other member has no photo: they do not get this one.
        $this->actingAs($otherUser)->get('/account/photo')->assertNotFound();
        $this->assertNull($other->fresh()->photo_path);

        $this->actingAs($user)->put('/account/profile', $this->validUpdate(['remove_photo' => '1']));
        $this->assertNull($member->fresh()->photo_path);
        Storage::disk('local')->assertMissing($second);
    }

    public function test_bad_photos_are_rejected(): void
    {
        [$user, $member] = $this->memberWithLogin('asha@example.test', 'Asha Verma');

        $this->actingAs($user)->put('/account/profile', $this->validUpdate(['photo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain')]))
            ->assertSessionHasErrors('photo');
        $this->actingAs($user)->put('/account/profile', $this->validUpdate(['photo' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')]))
            ->assertSessionHasErrors('photo');
        $this->actingAs($user)->put('/account/profile', $this->validUpdate(['photo' => UploadedFile::fake()->image('big.jpg', 100, 100)->size(3000)]))
            ->assertSessionHasErrors('photo');

        $this->assertNull($member->fresh()->photo_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    // ---------- Account state ----------

    public function test_member_cannot_use_the_admin_panel_or_admin_photo_route(): void
    {
        [$user, $member] = $this->memberWithLogin('asha@example.test', 'Asha Verma');

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($user)->get("/admin/members/{$member->id}")->assertForbidden();
        $this->actingAs($user)->get("/admin/members/{$member->id}/photo")->assertForbidden();
    }

    public function test_temporary_password_must_be_changed_before_the_profile_opens(): void
    {
        [$user] = $this->memberWithLogin('asha@example.test', 'Asha Verma');
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user->fresh())->get('/account')->assertRedirect('/account/password');
        $this->actingAs($user->fresh())->get('/account/profile')->assertRedirect('/account/password');
    }

    public function test_member_disabled_while_logged_in_is_sent_to_login(): void
    {
        [$user, $member] = $this->memberWithLogin('asha@example.test', 'Asha Verma');
        $admin = new User();
        $admin->forceFill(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'Password123!', 'is_active' => true,
            'is_super_admin' => true, 'role_id' => Role::where('name', 'super-admin')->value('id')])->save();

        $this->actingAs($user)->get('/account')->assertOk();

        $this->actingAs($admin)->post("/admin/members/{$member->id}/block", ['note' => 'Misconduct']);
        auth()->logout();

        $this->actingAs($user->fresh())->get('/account')->assertRedirect('/login');
        $this->assertGuest();
    }
}
