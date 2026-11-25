<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;

/**
 * The single row of settings used by the public application page, the ID card
 * and the joining letter. Image paths are never mass-assignable: they are only
 * set by CardSettingController after a validated upload.
 */
class CardSetting extends Model
{
    /** image key (used in URLs and form fields) => database column */
    public const IMAGES = [
        'logo' => 'logo_path',
        'payment_qr' => 'payment_qr_path',
        'website_qr' => 'website_qr_path',
        'stamp' => 'stamp_path',
    ];

    public const IMAGE_LABELS = [
        'logo' => 'Logo',
        'payment_qr' => 'Payment QR code',
        'website_qr' => 'Website QR code (printed on the ID card)',
        'stamp' => 'Approval stamp',
    ];

    public const AADHAAR_DISPLAY = ['full' => 'Full number', 'masked' => 'Masked (XXXX XXXX 1234)', 'hidden' => 'Not printed'];

    /** Placeholders allowed in the joining letter text, with what each one becomes. */
    public const PLACEHOLDERS = [
        'full_name' => 'Full name',
        'first_name' => 'First word of the name',
        'last_name' => 'Rest of the name',
        'designation' => 'Designation from these settings',
        'site_title' => 'Site or organization title',
        'joining_date' => 'Joining date',
        'id_number' => 'Membership number',
        'expiry_date' => 'ID card expiry date',
        'date' => 'Letter date',
        'email' => 'Member email',
        'mobile' => 'Member mobile',
        'address' => 'Member address',
        'city' => 'Member city',
        'district' => 'Member district',
        'state' => 'Member state',
        'work_area' => 'Member work area',
    ];

    /** Tags kept in the letter text. Everything else is removed, and attributes are stripped from these. */
    public const TEMPLATE_TAGS = '<p><br><strong><b><em><i><u><ol><ul><li><h3><h4>';

    protected $fillable = [
        'fee_amount', 'fee_currency',
        'card_heading', 'card_designation', 'card_validity_months', 'card_aadhaar_display',
        'card_terms', 'udyam_number', 'website_url', 'application_notice',
        'letter_site_title', 'letter_signatory_name', 'letter_signatory_role', 'letter_address',
        'letter_phone', 'letter_email', 'letter_date', 'letter_joining_date', 'letter_template',
    ];

    protected function casts(): array
    {
        return [
            'fee_amount' => 'decimal:2',
            'card_validity_months' => 'integer',
            'letter_date' => 'date',
            'letter_joining_date' => 'date',
        ];
    }

    /**
     * The settings row. Falls back to defaults if the table does not exist yet
     * (before "php artisan migrate"), so pages never crash.
     */
    public static function current(): self
    {
        try {
            return static::query()->first() ?? new static(static::defaults());
        } catch (QueryException) {
            return new static(static::defaults());
        }
    }

    public static function defaults(): array
    {
        return [
            'fee_amount' => null,
            'fee_currency' => '₹',
            'card_heading' => 'PRESS',
            'card_designation' => 'Reporter',
            'card_validity_months' => 12,
            'card_aadhaar_display' => 'full',
            'card_terms' => implode("\n", [
                'This card is non-transferable.',
                'In case of loss, please contact our office or the nearest police station.',
                'If the card holder is involved in any illegal, criminal or anti-social activity, the card is cancelled immediately and the card holder alone is responsible.',
                'The card holder may use this card only for news collection.',
                'The organization is not responsible for any anti-social or criminal activity done by the card holder.',
            ]),
            'letter_signatory_role' => 'Editor in Chief',
            'letter_template' => '<p>Dear {full_name},</p>'
                . '<p>We are pleased to inform you that you have been selected for the position of <strong>{designation}</strong> at {site_title}. We are excited to have you join our team and look forward to working with you.</p>'
                . '<p>Your official joining date will be {joining_date}. You are expected to report to our office at 9:00 AM on the first day. Please bring the following documents with you:</p>'
                . '<ol><li>Original educational certificates</li><li>A copy of your Aadhaar card</li><li>Recent passport size photographs (2)</li><li>Bank account details</li></ol>'
                . '<p>As a {designation}, you will be responsible for gathering, researching and reporting news content in line with our editorial standards. We believe your skills and enthusiasm will be a great addition to our team.</p>'
                . '<p>If you have any questions, feel free to contact us at the number or email provided below.</p>'
                . '<p>Once again, welcome to {site_title}. We look forward to a successful journey together.</p>',
        ];
    }

    public const DEFAULT_APPLICATION_NOTICE = 'संवाददाता के द्वारा भेजी गई खबर की जिम्मेदारी स्वयं संवाददाता की होगी। बिना साक्ष्य के कोई भी खबर का प्रकाशन नहीं की जाएगी।';

    /** The note under the application form (the default text until the admin writes another). */
    public function applicationNotice(): string
    {
        return filled($this->application_notice) ? $this->application_notice : self::DEFAULT_APPLICATION_NOTICE;
    }

    /** Text shown above the payment QR, or null when no fee is set (empty or zero). */
    public function feeLabel(): ?string
    {
        if ($this->fee_amount === null || (float) $this->fee_amount <= 0) {
            return null;
        }

        $fee = (float) $this->fee_amount;
        // Whole rupees without decimals (500), otherwise two decimals (499.50).
        $amount = floor($fee) === $fee ? number_format($fee, 0, '.', '') : number_format($fee, 2, '.', '');

        return 'Application fee: ' . ($this->fee_currency ?: '') . $amount;
    }

    /** @return array<int, string> the card's terms, one per line, blanks removed */
    public function termsList(): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/u', (string) $this->card_terms) ?: []),
            fn (string $line) => $line !== ''
        ));
    }

    /** ID card expiry: the validity in months counted from the given date (normally the approval date). */
    public function expiryDateFor(CarbonInterface $from): CarbonInterface
    {
        return $from->copy()->addMonthsNoOverflow(max(1, (int) $this->card_validity_months));
    }

    /** Absolute path of a stored image, or null when none is uploaded (or the file is gone). */
    public function imagePath(string $key): ?string
    {
        $column = self::IMAGES[$key] ?? null;
        $path = $column ? $this->{$column} : null;

        return $path && Storage::disk('local')->exists($path) ? Storage::disk('local')->path($path) : null;
    }

    public function hasImage(string $key): bool
    {
        return $this->imagePath($key) !== null;
    }

    /** Keeps only simple formatting tags in the letter text and removes every attribute. */
    public static function cleanTemplate(string $html): string
    {
        $html = strip_tags($html, self::TEMPLATE_TAGS);

        return trim((string) preg_replace('/<(\w+)\s[^>]*>/', '<$1>', $html));
    }
}
