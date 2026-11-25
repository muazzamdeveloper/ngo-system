<?php

namespace App\Services;

use App\Exceptions\PdfNotReady;
use App\Models\CardSetting;
use App\Models\Member;
use App\Models\OrganizationSetting;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Makes the ID card and joining letter PDFs with dompdf (installed by the user with
 * "composer require dompdf/dompdf").
 * Both documents are built here: the 2-page ID card (3.6) and the A4 joining letter (3.7).
 */
class CardPdfService
{
    public const ID_CARD = 'id-card';
    public const JOINING_LETTER = 'joining-letter';

    public const DOCUMENTS = [self::ID_CARD, self::JOINING_LETTER];

    /** Card artwork size in pixels; the photo is cut to the exact frame it is printed in. */
    private const PHOTO_WIDTH = 150;
    private const PHOTO_HEIGHT = 140;

    public function isAvailable(): bool
    {
        return class_exists(\Dompdf\Dompdf::class);
    }

    /**
     * Returns the PDF file contents.
     *
     * @throws PdfNotReady when the PDF library is missing or the document is not built yet
     */
    public function render(string $document, Member $member): string
    {
        if (! $this->isAvailable()) {
            throw new PdfNotReady('The PDF library is not installed yet, so this document cannot be made. Run: composer require dompdf/dompdf');
        }

        if (! in_array($document, self::DOCUMENTS, true)) {
            throw new PdfNotReady('Unknown document.');
        }

        try {
            return match ($document) {
                self::ID_CARD => $this->renderIdCard($member),
                self::JOINING_LETTER => $this->renderJoiningLetter($member),
            };
        } catch (Throwable $e) {
            // Any rendering failure (for example a missing PHP extension) becomes a clear message
            // for staff instead of an error page; the details go to the log for the developer.
            Log::error('PDF generation failed', ['document' => $document, 'member_id' => $member->id, 'error' => $e->getMessage()]);

            throw new PdfNotReady('This document could not be made right now. The details are in the log file (storage/logs/laravel.log).');
        }
    }

    /**
     * A ready download response (private, never cached, safe file name).
     *
     * @throws PdfNotReady
     */
    public function response(string $document, Member $member): Response
    {
        $contents = $this->render($document, $member);

        // The membership number is typed by staff, so only safe characters go into the file name.
        $suffix = preg_replace('/[^A-Za-z0-9._-]/', '', (string) $member->membership_number);
        $filename = $document . ($suffix !== '' ? '-' . $suffix : '') . '.pdf';

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // ---------- Joining letter ----------

    private function renderJoiningLetter(Member $member): string
    {
        $dompdf = $this->makeDompdf();
        $dompdf->loadHtml($this->joiningLetterHtml($member));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /** The HTML given to dompdf (public so it can be checked without the library). */
    public function joiningLetterHtml(Member $member, ?CardSetting $setting = null): string
    {
        $setting ??= CardSetting::current();

        return view('pdf.joining-letter', $this->joiningLetterData($member, $setting))->render();
    }

    private function joiningLetterData(Member $member, CardSetting $setting): array
    {
        $dash = '-';
        $format = 'F j, Y';

        $letterDate = $setting->letter_date ?? now();
        $joiningDate = $setting->letter_joining_date ?? $this->approvalDate($member);
        $expiry = $setting->expiryDateFor($this->approvalDate($member));

        $siteTitle = trim((string) $setting->letter_site_title);
        $siteTitle = $siteTitle !== '' ? $siteTitle : (string) OrganizationSetting::current()->name;

        // "Asha Devi Verma" -> first name "Asha", last name "Devi Verma".
        $parts = preg_split('/\s+/u', trim($member->full_name), 2) ?: [];
        $first = $parts[0] ?? '';
        $last = $parts[1] ?? '';

        $values = [
            'full_name' => $member->full_name,
            'first_name' => $first,
            'last_name' => $last,
            'designation' => trim((string) $setting->card_designation),
            'site_title' => $siteTitle,
            'joining_date' => $joiningDate->format($format),
            'id_number' => (string) $member->membership_number,
            'expiry_date' => $expiry->format($format),
            'date' => $letterDate->format($format),
            'email' => (string) $member->email,
            'mobile' => (string) $member->mobile,
            'address' => (string) $member->address,
            'city' => (string) $member->city,
            'district' => (string) $member->district,
            'state' => (string) $member->state,
            'work_area' => (string) $member->work_area,
        ];

        // Member data is escaped before it goes into the letter text.
        $replacements = [];
        foreach (array_keys(CardSetting::PLACEHOLDERS) as $key) {
            $replacements['{' . $key . '}'] = $key === 'address'
                ? nl2br(e($values[$key]))
                : e($values[$key] ?? '');
        }

        $template = trim((string) $setting->letter_template) !== ''
            ? (string) $setting->letter_template
            : CardSetting::defaults()['letter_template'];

        // Cleaned again after filling in, so the letter can only ever hold the simple tags.
        $content = CardSetting::cleanTemplate(strtr($template, $replacements));

        return [
            'css' => (string) file_get_contents(resource_path('pdf/joining-letter.css')),
            'siteTitle' => $siteTitle,
            'address' => trim((string) $setting->letter_address),
            'udyam' => trim((string) $setting->udyam_number),
            'signatory' => trim((string) $setting->letter_signatory_name),
            'role' => trim((string) $setting->letter_signatory_role),
            'phone' => trim((string) $setting->letter_phone),
            'email' => trim((string) $setting->letter_email),
            'letterDate' => $letterDate->format($format),
            'content' => $content,
            'logoUri' => $this->fileToDataUri($setting->imagePath('logo') ?? OrganizationSetting::current()->logoFilePath()),
            'iconPhone' => $this->fileToDataUri(resource_path('pdf/icons/icon-phone-white.png')),
            'iconMail' => $this->fileToDataUri(resource_path('pdf/icons/icon-mail-white.png')),
        ];
    }

    // ---------- ID card ----------

    /** Two pages: front, then terms and contact details. */
    private function renderIdCard(Member $member): string
    {
        $dompdf = $this->makeDompdf();
        $dompdf->loadHtml($this->idCardHtml($member));

        // Same size as the artwork (368 x 574 px = 276 x 430.5 pt) plus 0.2 pt, so rounding
        // can never push the card onto an extra blank page.
        $dompdf->setPaper([0, 0, 276.2, 430.7]);
        $dompdf->render();

        return $dompdf->output();
    }

    /** The HTML given to dompdf (public so it can be checked without the library). */
    public function idCardHtml(Member $member, ?CardSetting $setting = null): string
    {
        $setting ??= CardSetting::current();

        return view('pdf.id-card', $this->idCardData($member, $setting))->render();
    }

    private function idCardData(Member $member, CardSetting $setting): array
    {
        $dash = '-';

        $rows = [
            ['ID No.', $member->membership_number ?: $dash],
            ['Phone', $member->mobile ?: $dash],
        ];

        // Aadhaar on the card follows the Card Settings choice: full, masked or not printed.
        $aadhaar = $this->aadhaarForCard($member, (string) $setting->card_aadhaar_display);
        if ($aadhaar !== null) {
            $rows[] = ['Aadhaar No.', $aadhaar];
        }

        $rows[] = ['Work Area', $member->work_area ?: $dash];
        $rows[] = ['State', $member->state ?: $dash];

        $expiry = $setting->expiryDateFor($this->approvalDate($member));

        $terms = $setting->termsList();

        return [
            'css' => (string) file_get_contents(resource_path('pdf/id-card.css')),
            'heading' => $setting->card_heading ?: 'PRESS',
            'udyam' => (string) $setting->udyam_number,
            'designation' => trim((string) $setting->card_designation),
            'name' => $member->full_name,
            'rows' => $rows,
            'expiryLabel' => $expiry->format('d-M-Y'),
            'terms' => $terms,
            'address' => $member->address ?: $dash,
            'email' => $member->email ?: $dash,
            'phone' => $member->mobile ?: $dash,
            'site' => (string) preg_replace('#^https?://#i', '', rtrim((string) $setting->website_url, '/')),
            'logoUri' => $this->fileToDataUri($setting->imagePath('logo') ?? OrganizationSetting::current()->logoFilePath()),
            'stampUri' => $this->fileToDataUri($setting->imagePath('stamp')),
            'qrUri' => $this->fileToDataUri($setting->imagePath('website_qr')),
            'photoUri' => $this->photoToDataUri($member),
            'iconPin' => $this->fileToDataUri(resource_path('pdf/icons/icon-pin-red.png')),
            'iconMail' => $this->fileToDataUri(resource_path('pdf/icons/icon-mail-red.png')),
            'iconPhone' => $this->fileToDataUri(resource_path('pdf/icons/icon-phone-red.png')),
        ];
    }

    private function aadhaarForCard(Member $member, string $mode): ?string
    {
        if ($mode === 'hidden') {
            return null;
        }

        $full = $member->fullAadhaar();

        if ($full === null) {
            return '-';
        }

        if ($mode === 'masked') {
            return $member->maskedAadhaar();
        }

        return trim(chunk_split($full, 4, ' '));
    }

    /** Expiry counts from the approval date (older rows without one fall back to review or creation date). */
    private function approvalDate(Member $member): CarbonInterface
    {
        return $member->approved_at ?? $member->reviewed_at ?? $member->created_at ?? now();
    }

    // ---------- Images (dompdf cannot reach private storage, so they are embedded) ----------

    private function fileToDataUri(?string $path): string
    {
        if (! $path || ! is_file($path)) {
            return '';
        }

        $info = @getimagesize($path);
        $mime = $info['mime'] ?? '';

        // dompdf draws JPEG, PNG and GIF; anything else is left out rather than breaking the PDF.
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            return '';
        }

        $data = @file_get_contents($path);

        return $data === false ? '' : 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * Cuts the member photo to the shape it is printed in (dompdf ignores CSS object-fit, so a
     * tall photo would be squashed) and re-encodes it as JPEG. This also turns WebP photos,
     * which dompdf cannot draw, into something it can. Without the GD extension only
     * JPEG/PNG photos are used as they are.
     */
    private function photoToDataUri(Member $member): string
    {
        if (! $member->photo_path) {
            return '';
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        if (! $disk->exists($member->photo_path)) {
            return '';
        }

        $path = $disk->path($member->photo_path);

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return $this->fileToDataUri($path);
        }

        try {
            $raw = @file_get_contents($path);
            $src = $raw === false ? false : @imagecreatefromstring($raw);

            if (! $src) {
                return $this->fileToDataUri($path);
            }

            $sw = imagesx($src);
            $sh = imagesy($src);

            if ($sw < 1 || $sh < 1) {
                imagedestroy($src);

                return '';
            }

            $ratio = self::PHOTO_WIDTH / self::PHOTO_HEIGHT;

            if (($sw / $sh) > $ratio) {
                $cw = (int) round($sh * $ratio);
                $ch = $sh;
                $cx = (int) round(($sw - $cw) / 2);
                $cy = 0;
            } else {
                $cw = $sw;
                $ch = (int) round($sw / $ratio);
                $cx = 0;
                $cy = (int) round(($sh - $ch) * 0.2); // keep faces: crop a little more from the bottom
            }

            $dw = self::PHOTO_WIDTH * 3;
            $dh = self::PHOTO_HEIGHT * 3;
            $dst = imagecreatetruecolor($dw, $dh);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, $cx, $cy, $dw, $dh, $cw, $ch);

            ob_start();
            imagejpeg($dst, null, 90);
            $jpeg = (string) ob_get_clean();

            imagedestroy($src);
            imagedestroy($dst);

            return $jpeg === '' ? '' : 'data:image/jpeg;base64,' . base64_encode($jpeg);
        } catch (Throwable) {
            return '';
        }
    }

    // ---------- dompdf ----------

    private function makeDompdf(): \Dompdf\Dompdf
    {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);      // images are embedded, nothing is fetched from the internet
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('chroot', [resource_path('pdf')]);

        return new \Dompdf\Dompdf($options);
    }
}
