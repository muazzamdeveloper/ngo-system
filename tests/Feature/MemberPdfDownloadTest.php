<?php

namespace Tests\Feature;

use App\Models\CardSetting;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberPdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function staff(string $roleName, bool $super = false): User
    {
        // Same person on repeated calls, but always a fresh object so role changes are picked up.
        $existing = User::where('email', $roleName . '@example.test')->first();

        if ($existing) {
            return $existing;
        }

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

    private int $numberCounter = 0;

    private function approved(string $status = Member::STATUS_APPROVED): Member
    {
        $member = Member::factory()->create();
        $number = 'LM-2026-' . str_pad((string) ++$this->numberCounter, 4, '0', STR_PAD_LEFT);
        $member->forceFill(['status' => $status, 'membership_number' => $number, 'approved_at' => now()])->save();

        return $member;
    }

    private function pdfReady(): bool
    {
        return class_exists(\Dompdf\Dompdf::class);
    }

    private function setAadhaarMode(string $mode): void
    {
        $setting = CardSetting::current();
        $setting->card_aadhaar_display = $mode;
        $setting->save();
    }

    private function giveViewAadhaarOnly(string $roleName, bool $give): void
    {
        $role = Role::where('name', $roleName)->first();
        $id = Permission::where('name', 'members.view_aadhaar')->value('id');
        // Access is per account now: the super admin grants it to each account holding this role.
        foreach (User::where('role_id', $role->id)->get() as $account) {
            $give ? $account->directPermissions()->syncWithoutDetaching([$id]) : $account->directPermissions()->detach($id);
        }
    }

    // ---------- Admin ----------

    public function test_guest_and_member_role_cannot_download_from_admin(): void
    {
        $member = $this->approved();

        $this->get("/admin/members/{$member->id}/pdf/joining-letter")->assertRedirect('/login');
        $this->actingAs($this->staff('member'))->get("/admin/members/{$member->id}/pdf/joining-letter")->assertForbidden();
    }

    public function test_only_approved_members_have_documents(): void
    {
        $admin = $this->staff('manager');

        foreach ([Member::STATUS_PENDING, Member::STATUS_REJECTED, Member::STATUS_BLOCKED, Member::STATUS_DEACTIVATED] as $status) {
            $member = $this->approved($status);
            $this->actingAs($admin)->get("/admin/members/{$member->id}/pdf/joining-letter")->assertNotFound();
        }
    }

    public function test_unknown_document_name_is_not_found(): void
    {
        $member = $this->approved();

        $this->actingAs($this->staff('manager'))->get("/admin/members/{$member->id}/pdf/passport")->assertNotFound();
    }

    public function test_staff_get_the_letter_or_a_clear_message_when_dompdf_is_missing(): void
    {
        $member = $this->approved();

        $response = $this->actingAs($this->staff('manager'))
            ->from("/admin/members/{$member->id}")
            ->get("/admin/members/{$member->id}/pdf/joining-letter");

        if ($this->pdfReady()) {
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        } else {
            $response->assertRedirect("/admin/members/{$member->id}")->assertSessionHas('error');
        }
    }

    public function test_full_aadhaar_card_needs_the_aadhaar_permission(): void
    {
        $member = $this->approved();
        $this->setAadhaarMode('full');

        $this->giveViewAadhaarOnly('coordinator', false);
        $this->actingAs($this->staff('coordinator'))->get("/admin/members/{$member->id}/pdf/id-card")->assertForbidden();

        $this->giveViewAadhaarOnly('coordinator', true);
        $response = $this->actingAs($this->staff('coordinator'))->get("/admin/members/{$member->id}/pdf/id-card");
        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_masked_card_does_not_need_the_aadhaar_permission(): void
    {
        $member = $this->approved();
        $this->setAadhaarMode('masked');
        $this->giveViewAadhaarOnly('coordinator', false);

        $response = $this->actingAs($this->staff('coordinator'))->get("/admin/members/{$member->id}/pdf/id-card");

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_joining_letter_does_not_need_the_aadhaar_permission(): void
    {
        $member = $this->approved();
        $this->setAadhaarMode('full');
        $this->giveViewAadhaarOnly('coordinator', false);

        $response = $this->actingAs($this->staff('coordinator'))->get("/admin/members/{$member->id}/pdf/joining-letter");

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_profile_shows_buttons_only_for_approved_members(): void
    {
        $admin = $this->staff('manager');
        $this->giveViewAadhaarOnly('manager', true);
        $approved = $this->approved();
        $pending = $this->approved(Member::STATUS_PENDING);

        $this->actingAs($admin)->get("/admin/members/{$approved->id}")
            ->assertSee('Download ID card')->assertSee('Download joining letter');

        $this->actingAs($admin)->get("/admin/members/{$pending->id}")
            ->assertDontSee('Download ID card')->assertDontSee('Download joining letter');
    }

    public function test_profile_hides_the_id_card_button_when_it_would_print_the_full_aadhaar(): void
    {
        $this->setAadhaarMode('full');
        $this->giveViewAadhaarOnly('coordinator', false);
        $member = $this->approved();

        $this->actingAs($this->staff('coordinator'))->get("/admin/members/{$member->id}")
            ->assertDontSee('Download ID card')->assertSee('Download joining letter')->assertSee('restricted');
    }

    // ---------- The member's own page ----------

    private function memberLogin(Member $member): User
    {
        $user = new User();
        $user->forceFill([
            'name' => $member->full_name,
            'email' => 'login-' . $member->id . '@example.test',
            'password' => 'Password123!',
            'is_active' => true,
            'role_id' => Role::where('name', 'member')->value('id'),
            'must_change_password' => false,
        ])->save();

        $member->forceFill(['user_id' => $user->id])->save();

        return $user;
    }

    public function test_member_sees_download_buttons_when_approved(): void
    {
        $member = $this->approved();
        $user = $this->memberLogin($member);

        $this->actingAs($user)->get('/account')
            ->assertOk()->assertSee('Download ID card')->assertSee('Download joining letter');
    }

    public function test_member_sees_a_waiting_note_when_not_approved(): void
    {
        $member = $this->approved(Member::STATUS_PENDING);
        $user = $this->memberLogin($member);

        $this->actingAs($user)->get('/account')
            ->assertDontSee('Download ID card')->assertSee('once your membership is approved');
    }

    public function test_member_can_download_only_their_own_documents(): void
    {
        $member = $this->approved();
        $user = $this->memberLogin($member);

        $response = $this->actingAs($user)->from('/account')->get('/account/documents/joining-letter');

        if ($this->pdfReady()) {
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        } else {
            $response->assertRedirect('/account')->assertSessionHas('error');
        }
    }

    public function test_member_who_is_not_approved_or_unlinked_gets_not_found(): void
    {
        $pending = $this->memberLogin($this->approved(Member::STATUS_PENDING));
        $this->actingAs($pending)->get('/account/documents/id-card')->assertNotFound();

        $loose = new User();
        $loose->forceFill([
            'name' => 'No Record', 'email' => 'norecord@example.test', 'password' => 'Password123!',
            'is_active' => true, 'role_id' => Role::where('name', 'member')->value('id'), 'must_change_password' => false,
        ])->save();
        $this->actingAs($loose)->get('/account/documents/id-card')->assertNotFound();
    }

    public function test_guest_cannot_use_the_account_download(): void
    {
        $this->get('/account/documents/id-card')->assertRedirect('/login');
    }

    public function test_unknown_document_name_is_not_found_on_the_account_page(): void
    {
        $user = $this->memberLogin($this->approved());

        $this->actingAs($user)->get('/account/documents/passport')->assertNotFound();
    }
}
