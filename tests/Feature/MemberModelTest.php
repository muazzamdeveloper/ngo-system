<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_member_is_pending_without_membership_number(): void
    {
        $member = Member::factory()->create()->fresh();

        $this->assertSame(Member::STATUS_PENDING, $member->status);
        $this->assertNull($member->membership_number);
        $this->assertNull($member->user_id);
    }

    public function test_aadhaar_is_encrypted_in_the_database(): void
    {
        $member = Member::factory()->withAadhaar('1234 5678 9012')->create();

        $raw = DB::table('members')->where('id', $member->id)->first();

        $this->assertStringNotContainsString('123456789012', json_encode($raw));
        $this->assertNotNull($raw->aadhaar_encrypted);
        $this->assertSame('9012', $raw->aadhaar_last4);

        $fresh = $member->fresh();
        $this->assertSame('123456789012', $fresh->fullAadhaar());
        $this->assertSame('XXXX XXXX 9012', $fresh->maskedAadhaar());
    }

    public function test_aadhaar_never_appears_in_array_or_json(): void
    {
        $member = Member::factory()->withAadhaar('123456789012')->create()->fresh();

        $this->assertStringNotContainsString('123456789012', $member->toJson());
        $this->assertArrayNotHasKey('aadhaar_encrypted', $member->toArray());
        $this->assertArrayNotHasKey('aadhaar_hash', $member->toArray());
    }

    public function test_member_can_be_found_by_aadhaar_without_decrypting(): void
    {
        $member = Member::factory()->withAadhaar('123456789012')->create();
        Member::factory()->withAadhaar('999988887777')->create();

        $this->assertSame($member->id, Member::findByAadhaar('1234-5678-9012')->id);
        $this->assertNull(Member::findByAadhaar('111122223333'));
    }

    public function test_duplicate_aadhaar_is_rejected_by_the_database(): void
    {
        Member::factory()->withAadhaar('123456789012')->create();

        $this->expectException(QueryException::class);
        Member::factory()->withAadhaar('123456789012')->create();
    }

    public function test_members_without_aadhaar_do_not_conflict(): void
    {
        Member::factory()->count(2)->create();

        $this->assertSame(2, Member::count());
        $this->assertSame('Not provided', Member::first()->maskedAadhaar());
    }

    public function test_sensitive_columns_cannot_be_mass_assigned(): void
    {
        $member = Member::create([
            'full_name' => 'Test Person',
            'father_name' => 'Test Father',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'mobile' => '9800000000',
            'address' => 'Somewhere',
            'city' => 'City',
            'state' => 'State',
            'pincode' => '123456',
            'status' => Member::STATUS_APPROVED,
            'membership_number' => 'HACK-0001',
            'aadhaar_last4' => '1111',
        ])->fresh();

        $this->assertSame(Member::STATUS_PENDING, $member->status);
        $this->assertNull($member->membership_number);
        $this->assertNull($member->aadhaar_last4);
    }

    public function test_status_scopes(): void
    {
        Member::factory()->count(2)->create();
        Member::factory()->approved()->create();

        $this->assertSame(2, Member::pending()->count());
        $this->assertSame(1, Member::approved()->count());
    }
}
