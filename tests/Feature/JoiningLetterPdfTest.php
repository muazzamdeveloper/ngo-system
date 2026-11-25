<?php

namespace Tests\Feature;

use App\Models\CardSetting;
use App\Models\Member;
use App\Services\CardPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JoiningLetterPdfTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function member(array $attributes = []): Member
    {
        $member = Member::factory()->create(array_merge([
            'full_name' => 'Asha Devi Verma',
            'mobile' => '9876543210',
            'email' => 'asha@example.test',
            'address' => "12 Park Road\nNear Station",
            'city' => 'Lucknow',
            'district' => 'Lucknow',
            'work_area' => 'Hazratganj',
            'state' => 'Uttar Pradesh',
        ], $attributes));

        $member->forceFill([
            'status' => Member::STATUS_APPROVED,
            'membership_number' => 'LM-2026-0001',
            'approved_at' => '2026-01-15 10:00:00',
        ])->save();

        return $member;
    }

    private function settings(array $overrides = []): CardSetting
    {
        return new CardSetting(array_merge(CardSetting::defaults(), [
            'letter_site_title' => 'Lizer Media',
            'letter_signatory_name' => 'R. Sharma',
            'letter_signatory_role' => 'Editor in Chief',
            'letter_address' => "1 Press Lane\nLucknow",
            'letter_phone' => '0522-111111',
            'letter_email' => 'office@example.org',
            'udyam_number' => 'UDYAM-UP-00-0000001',
        ], $overrides));
    }

    private function html(Member $member, ?CardSetting $setting = null): string
    {
        return app(CardPdfService::class)->joiningLetterHtml($member, $setting ?? $this->settings());
    }

    public function test_default_letter_uses_the_member_and_settings(): void
    {
        $html = $this->html($this->member());

        foreach (['Dear Asha Devi Verma', 'Reporter', 'Lizer Media', 'Joining Letter', 'R. Sharma', 'Editor in Chief',
            '0522-111111', 'office@example.org', 'UDYAM-UP-00-0000001', '1 Press Lane'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_no_placeholder_is_left_unfilled(): void
    {
        $template = '<p>' . implode(' ', array_map(fn ($k) => '{' . $k . '}', array_keys(CardSetting::PLACEHOLDERS))) . '</p>';

        $html = $this->html($this->member(), $this->settings(['letter_template' => $template]));

        foreach (array_keys(CardSetting::PLACEHOLDERS) as $key) {
            $this->assertStringNotContainsString('{' . $key . '}', $html, "{{$key}} was not replaced");
        }
    }

    public function test_name_is_split_into_first_and_last(): void
    {
        $html = $this->html($this->member(), $this->settings(['letter_template' => '<p>[{first_name}] [{last_name}]</p>']));

        $this->assertStringContainsString('[Asha] [Devi Verma]', $html);
    }

    public function test_single_word_name_leaves_last_name_empty(): void
    {
        $html = $this->html($this->member(['full_name' => 'Asha']), $this->settings(['letter_template' => '<p>[{first_name}] [{last_name}]</p>']));

        $this->assertStringContainsString('[Asha] []', $html);
    }

    public function test_dates_membership_number_and_expiry(): void
    {
        $member = $this->member(); // approved 15 Jan 2026
        $setting = $this->settings([
            'card_validity_months' => 12,
            'letter_date' => '2026-03-01',
            'letter_template' => '<p>{date}|{joining_date}|{id_number}|{expiry_date}</p>',
        ]);

        // Joining date left empty: the approval date is used.
        $this->assertStringContainsString('March 1, 2026|January 15, 2026|LM-2026-0001|January 15, 2027', $this->html($member, $setting));

        $setting->letter_joining_date = '2026-02-10';
        $this->assertStringContainsString('March 1, 2026|February 10, 2026', $this->html($member, $setting));
    }

    public function test_empty_letter_date_uses_today(): void
    {
        $html = $this->html($this->member(), $this->settings(['letter_template' => '<p>{date}</p>']));

        $this->assertStringContainsString(now()->format('F j, Y'), $html);
    }

    public function test_empty_site_title_uses_the_organization_name(): void
    {
        $html = $this->html($this->member(), $this->settings(['letter_site_title' => '']));

        $this->assertStringContainsString(\App\Models\OrganizationSetting::current()->name, $html);
    }

    public function test_member_data_is_escaped_in_the_letter(): void
    {
        $member = $this->member(['full_name' => '<script>alert(1)</script> Kumar']);

        $html = $this->html($member);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_address_keeps_its_line_breaks(): void
    {
        $html = $this->html($this->member(), $this->settings(['letter_template' => '<p>{address}</p>']));

        $this->assertMatchesRegularExpression('#12 Park Road<br>\s*Near Station#', $html);
    }

    public function test_only_simple_tags_survive_in_the_letter_text(): void
    {
        $html = $this->html($this->member(), $this->settings([
            'letter_template' => '<p onclick="x()">Hello</p><script>bad()</script><img src="http://evil.test/a.png">',
        ]));

        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('<img src="http://evil.test', $html);
        $this->assertStringNotContainsString('<script>bad()', $html);
        $this->assertStringContainsString('<p>Hello</p>', $html);
    }

    public function test_empty_template_falls_back_to_the_default_letter(): void
    {
        $html = $this->html($this->member(), $this->settings(['letter_template' => '']));

        $this->assertStringContainsString('We are pleased to inform you', $html);
    }

    public function test_signature_block_is_left_out_when_no_signatory_is_set(): void
    {
        $html = $this->html($this->member(), $this->settings(['letter_signatory_name' => '', 'letter_signatory_role' => '']));

        $this->assertStringNotContainsString('Best regards', $html);
    }

    public function test_logo_is_embedded_when_uploaded(): void
    {
        Storage::disk('local')->put('card-settings/logo.png', base64_decode(self::PNG));

        $setting = $this->settings();
        $setting->forceFill(['logo_path' => 'card-settings/logo.png']);

        $html = $this->html($this->member(), $setting);

        $this->assertStringContainsString('class="jl-logo" src="data:image/png;base64,', $html);
        $this->assertStringNotContainsString('card-settings/', $html);
    }

    public function test_letter_does_not_contain_the_aadhaar_number(): void
    {
        $member = $this->member();
        $member->setAadhaar('234567890124')->save();

        $this->assertStringNotContainsString('234567890124', $this->html($member));
    }

    public function test_pdf_is_a_single_a4_page(): void
    {
        if (! class_exists(\Dompdf\Dompdf::class)) {
            $this->markTestSkipped('dompdf is not installed yet (composer require dompdf/dompdf).');
        }

        $pdf = app(CardPdfService::class)->render(CardPdfService::JOINING_LETTER, $this->member());

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(1, preg_match_all('#/Type\s*/Page(?![a-z])#', $pdf));
    }
}
