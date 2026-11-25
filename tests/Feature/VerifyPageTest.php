<?php

namespace Tests\Feature;

use App\Http\Controllers\VerifyController;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerifyPageTest extends TestCase
{
    use RefreshDatabase;

    private const AADHAAR = '234567890124';
    private const OTHER_AADHAAR = '298765432101';

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('verify:127.0.0.1');
    }

    private int $numberCounter = 0;

    private function member(string $status, ?string $aadhaar = self::AADHAAR): Member
    {
        $member = Member::factory()->create(['full_name' => 'Secret Person']);
        $member->setAadhaar($aadhaar);
        $number = 'LM-2026-' . str_pad((string) ++$this->numberCounter, 4, '0', STR_PAD_LEFT);
        $member->forceFill(['status' => $status, 'membership_number' => $number])->save();

        return $member;
    }

    private function signed(Member $member, string $document = 'id-card', int $minutes = 15): string
    {
        return URL::temporarySignedRoute('verify.download', now()->addMinutes($minutes), [
            'member' => $member->id, 'document' => $document,
        ]);
    }

    public function test_page_is_public(): void
    {
        $this->get('/verify')->assertOk()->assertSee('Enter your Aadhaar number');
    }

    public function test_approved_member_sees_verified_and_download_links_but_no_personal_data(): void
    {
        $this->member(Member::STATUS_APPROVED);

        $this->post('/verify', ['aadhaar' => self::AADHAAR])
            ->assertOk()
            ->assertSee('Membership verified')
            ->assertSee('Download member card')
            ->assertSee('Download member joining letter')
            ->assertSee('/verify/download/', false)
            ->assertSee('signature=', false)
            ->assertDontSee('Secret Person')
            ->assertDontSee('LM-2026-0001')
            ->assertDontSee(self::AADHAAR)
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_spaces_in_the_number_are_accepted(): void
    {
        $this->member(Member::STATUS_APPROVED);

        $this->post('/verify', ['aadhaar' => '2345 6789 0124'])->assertOk()->assertSee('Membership verified');
    }

    public function test_every_non_approved_case_gives_the_same_message(): void
    {
        $this->member(Member::STATUS_PENDING, '234567890124');
        $this->member(Member::STATUS_REJECTED, '298765432101');

        $responses = [];

        foreach ([Member::STATUS_BLOCKED, Member::STATUS_DEACTIVATED] as $i => $status) {
            // Make two more members with valid-looking numbers so each status is covered.
            $number = ['345678901238', '456789012341'][$i];
            $m = Member::factory()->create();
            $m->setAadhaar($number)->forceFill(['status' => $status])->save();
            $responses[] = $number;
        }

        $numbers = array_merge(['234567890124', '298765432101'], $responses, ['567890123458']); // last: no such member

        $messages = [];
        foreach ($numbers as $number) {
            RateLimiter::clear('verify:127.0.0.1');

            $response = $this->from('/verify')->post('/verify', ['aadhaar' => $number]);
            $response->assertRedirect('/verify');
            $messages[] = session('errors')->first('form');
        }

        $this->assertCount(1, array_unique($messages));
        $this->assertSame(VerifyController::NOT_VERIFIED_MESSAGE, $messages[0]);
    }

    public function test_message_does_not_reveal_status_or_name(): void
    {
        $this->member(Member::STATUS_PENDING);

        $this->followingRedirects()->from('/verify')->post('/verify', ['aadhaar' => self::AADHAAR])
            ->assertSee(VerifyController::NOT_VERIFIED_MESSAGE)
            ->assertDontSee('pending')->assertDontSee('Secret Person')
            ->assertDontSee('value="' . self::AADHAAR . '"', false);
    }

    public function test_invalid_number_is_rejected(): void
    {
        $this->from('/verify')->post('/verify', ['aadhaar' => '12345'])->assertSessionHasErrors('aadhaar');
        $this->from('/verify')->post('/verify', ['aadhaar' => ''])->assertSessionHasErrors('aadhaar');
    }

    public function test_aadhaar_is_not_put_back_into_the_session_after_an_error(): void
    {
        $this->from('/verify')->post('/verify', ['aadhaar' => '12345']);

        $this->assertNull(session()->getOldInput('aadhaar'));
    }

    public function test_attempts_are_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->from('/verify')->post('/verify', ['aadhaar' => '567890123458']);
        }

        $this->from('/verify')->post('/verify', ['aadhaar' => '567890123458'])
            ->assertRedirect('/verify')
            ->assertSessionHasErrors('form');

        $this->assertStringContainsString('Too many attempts', session('errors')->first('form'));
    }

    public function test_rate_limit_also_stops_an_approved_number(): void
    {
        $this->member(Member::STATUS_APPROVED);

        for ($i = 0; $i < 10; $i++) {
            $this->from('/verify')->post('/verify', ['aadhaar' => '567890123458']);
        }

        $this->from('/verify')->post('/verify', ['aadhaar' => self::AADHAAR])
            ->assertRedirect('/verify')->assertSessionHasErrors('form');
    }

    public function test_download_needs_a_valid_signature(): void
    {
        $member = $this->member(Member::STATUS_APPROVED);

        $this->get("/verify/download/{$member->id}/id-card")->assertForbidden();
        $this->get($this->signed($member) . 'x')->assertForbidden();
    }

    public function test_expired_link_is_refused(): void
    {
        $member = $this->member(Member::STATUS_APPROVED);
        $url = $this->signed($member);

        $this->travel(16)->minutes();

        $this->get($url)->assertForbidden();
    }

    public function test_link_cannot_be_reused_for_another_member_or_document(): void
    {
        $a = $this->member(Member::STATUS_APPROVED);
        $b = $this->member(Member::STATUS_APPROVED, self::OTHER_AADHAAR);

        $url = $this->signed($a);

        $this->get(str_replace("/{$a->id}/", "/{$b->id}/", $url))->assertForbidden();
        $this->get(str_replace('/id-card', '/joining-letter', $url))->assertForbidden();
    }

    public function test_unknown_document_name_is_not_found(): void
    {
        $member = $this->member(Member::STATUS_APPROVED);

        $url = URL::temporarySignedRoute('verify.download', now()->addMinutes(5), ['member' => $member->id, 'document' => 'id-card']);
        $this->get(str_replace('/id-card', '/passwd', $url))->assertNotFound();
    }

    public function test_link_stops_working_once_the_member_is_no_longer_approved(): void
    {
        $member = $this->member(Member::STATUS_APPROVED);
        $url = $this->signed($member);

        $member->forceFill(['status' => Member::STATUS_BLOCKED])->save();

        $this->get($url)->assertNotFound();
    }

    public function test_valid_links_download_or_show_a_clear_message(): void
    {
        $member = $this->member(Member::STATUS_APPROVED);

        // Both PDFs need the dompdf library, which is installed at the end of Part 3.
        foreach (['id-card', 'joining-letter'] as $document) {
            $response = $this->get($this->signed($member, $document));

            if (class_exists(\Dompdf\Dompdf::class)) {
                $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            } else {
                $response->assertStatus(503)
                    ->assertSee('Document not ready')
                    ->assertHeader('Cache-Control', 'no-store, private');
            }
        }
    }

    public function test_verified_page_links_work_end_to_end(): void
    {
        $this->member(Member::STATUS_APPROVED);

        $html = $this->post('/verify', ['aadhaar' => self::AADHAAR])->getContent();

        preg_match('#href="([^"]*/verify/download/[^"]*id-card[^"]*)"#', $html, $found);
        $this->assertNotEmpty($found);

        $this->get(html_entity_decode($found[1]))
            ->assertStatus(class_exists(\Dompdf\Dompdf::class) ? 200 : 503);
    }
}
