<?php

namespace Tests\Feature;

use App\Exceptions\PdfNotReady;
use App\Models\CardSetting;
use App\Models\Member;
use App\Services\CardPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdCardPdfTest extends TestCase
{
    use RefreshDatabase;

    private const AADHAAR = '234567890124';

    /** A valid 1x1 PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function member(array $attributes = []): Member
    {
        $member = Member::factory()->create(array_merge([
            'full_name' => 'Asha Verma',
            'mobile' => '9876543210',
            'email' => 'asha@example.test',
            'address' => '12 Park Road',
            'work_area' => 'Hazratganj',
            'state' => 'Uttar Pradesh',
        ], $attributes));

        $member->setAadhaar(self::AADHAAR);
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
            'website_url' => 'https://example.org/',
            'udyam_number' => 'UDYAM-UP-00-0000001',
        ], $overrides));
    }

    private function html(Member $member, ?CardSetting $setting = null): string
    {
        return app(CardPdfService::class)->idCardHtml($member, $setting ?? $this->settings());
    }

    private function storeImage(string $path): void
    {
        Storage::disk('local')->put($path, base64_decode(self::PNG));
    }

    public function test_card_shows_the_member_and_the_settings_texts(): void
    {
        $html = $this->html($this->member());

        foreach (['Asha Verma', 'Reporter', 'PRESS', 'UDYAM-UP-00-0000001', 'LM-2026-0001', '9876543210',
            'Hazratganj', 'Uttar Pradesh', '12 Park Road', 'asha@example.test', 'example.org'] as $text) {
            $this->assertStringContainsString($text, $html);
        }

        $this->assertStringNotContainsString('https://example.org', $html);
    }

    public function test_heading_designation_and_terms_follow_card_settings(): void
    {
        $html = $this->html($this->member(), $this->settings([
            'card_heading' => 'MEMBER',
            'card_designation' => 'Volunteer',
            'card_terms' => "First custom term\nSecond custom term",
        ]));

        $this->assertStringContainsString('MEMBER', $html);
        $this->assertStringContainsString('Volunteer', $html);
        $this->assertStringContainsString('First custom term', $html);
        $this->assertStringContainsString('Second custom term', $html);
        $this->assertStringNotContainsString('non-transferable', $html);
    }

    public function test_empty_designation_prints_no_designation_line(): void
    {
        $html = $this->html($this->member(), $this->settings(['card_designation' => '']));

        $this->assertStringNotContainsString('<p>Reporter</p>', $html);
    }

    public function test_aadhaar_full_masked_and_hidden(): void
    {
        $member = $this->member();

        $full = $this->html($member, $this->settings(['card_aadhaar_display' => 'full']));
        $this->assertStringContainsString('2345 6789 0124', $full);

        $masked = $this->html($member, $this->settings(['card_aadhaar_display' => 'masked']));
        $this->assertStringContainsString('XXXX XXXX 0124', $masked);
        $this->assertStringNotContainsString('2345 6789', $masked);
        $this->assertStringNotContainsString(self::AADHAAR, $masked);

        $hidden = $this->html($member, $this->settings(['card_aadhaar_display' => 'hidden']));
        $this->assertStringNotContainsString('Aadhaar No.', $hidden);
        $this->assertStringNotContainsString('0124', $hidden);
    }

    public function test_expiry_counts_from_the_approval_date(): void
    {
        $member = $this->member(); // approved 15 Jan 2026

        $this->assertStringContainsString('Expiry: 15-Jan-2027', $this->html($member, $this->settings(['card_validity_months' => 12])));
        $this->assertStringContainsString('Expiry: 15-Jul-2026', $this->html($member, $this->settings(['card_validity_months' => 6])));
    }

    public function test_expiry_does_not_overflow_at_month_end(): void
    {
        $member = $this->member();
        $member->forceFill(['approved_at' => '2026-08-31 09:00:00'])->save();

        $this->assertStringContainsString('Expiry: 28-Feb-2027', $this->html($member, $this->settings(['card_validity_months' => 6])));
    }

    public function test_text_is_escaped(): void
    {
        $html = $this->html($this->member(['full_name' => '<script>alert(1)</script>', 'address' => '<b>x</b>']));

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<b>x</b>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_images_from_card_settings_are_embedded_not_linked(): void
    {
        $this->storeImage('card-settings/logo.png');
        $this->storeImage('card-settings/stamp.png');
        $this->storeImage('card-settings/qr.png');

        $setting = $this->settings();
        $setting->forceFill(['logo_path' => 'card-settings/logo.png', 'stamp_path' => 'card-settings/stamp.png', 'website_qr_path' => 'card-settings/qr.png']);

        $html = $this->html($this->member(), $setting);

        $this->assertStringContainsString('class="logo" src="data:image/png;base64,', $html);
        $this->assertStringContainsString('class="stamp" src="data:image/png;base64,', $html);
        $this->assertStringContainsString('Website QR', $html);
        $this->assertStringNotContainsString('card-settings/', $html); // no storage path leaks into the PDF
    }

    public function test_card_works_without_any_images(): void
    {
        $html = $this->html($this->member());

        $this->assertStringNotContainsString('class="logo"', $html);
        $this->assertStringNotContainsString('class="stamp"', $html);
        $this->assertStringNotContainsString('Website QR', $html);
        $this->assertStringContainsString('profile-photo-placeholder', $html);
    }

    public function test_a_missing_image_file_is_skipped_without_error(): void
    {
        $setting = $this->settings();
        $setting->forceFill(['logo_path' => 'card-settings/gone.png']);

        $html = $this->html($this->member(['photo_path' => 'members/photos/gone.jpg']), $setting);

        $this->assertStringNotContainsString('class="logo"', $html);
        $this->assertStringContainsString('profile-photo-placeholder', $html);
    }

    public function test_member_photo_is_embedded(): void
    {
        $this->storeImage('members/photos/asha.png');

        $html = $this->html($this->member(['photo_path' => 'members/photos/asha.png']));

        $this->assertStringContainsString('class="profile-photo" src="data:image/', $html);
        $this->assertStringNotContainsString('<div class="profile-photo-placeholder"', $html); // the CSS rule itself may remain
    }

    public function test_card_has_a_front_and_a_back_with_terms(): void
    {
        $html = $this->html($this->member());

        $this->assertStringContainsString('front-page', $html);
        $this->assertStringContainsString('back-page', $html);
        $this->assertStringContainsString('Terms &amp; Conditions', $html);
        $this->assertSame(5, substr_count($html, '<li>')); // the five default terms
    }

    public function test_unknown_document_is_not_ready(): void
    {
        $this->expectException(PdfNotReady::class);

        app(CardPdfService::class)->render('passport', $this->member());
    }

    public function test_pdf_has_exactly_two_pages(): void
    {
        if (! class_exists(\Dompdf\Dompdf::class)) {
            $this->markTestSkipped('dompdf is not installed yet (composer require dompdf/dompdf).');
        }

        $this->storeImage('card-settings/logo.png');
        CardSetting::current()->forceFill(['logo_path' => 'card-settings/logo.png'])->save();

        $pdf = app(CardPdfService::class)->render(CardPdfService::ID_CARD, $this->member());

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(2, preg_match_all('#/Type\s*/Page(?![a-z])#', $pdf));
    }

    public function test_render_without_the_library_says_not_ready(): void
    {
        if (class_exists(\Dompdf\Dompdf::class)) {
            $this->markTestSkipped('dompdf is installed.');
        }

        $this->expectException(PdfNotReady::class);

        app(CardPdfService::class)->render(CardPdfService::ID_CARD, $this->member());
    }
}
