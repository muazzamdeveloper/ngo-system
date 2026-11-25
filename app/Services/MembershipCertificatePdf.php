<?php

namespace App\Services;

use App\Exceptions\PdfNotReady;
use App\Models\CardSetting;
use App\Models\MembershipCertificate;
use App\Models\OrganizationSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Makes the printable membership certificate (A4 landscape) with dompdf, the same library
 * the ID card and joining letter use. Staff download it from the Membership certificates list.
 */
class MembershipCertificatePdf
{
    public function isAvailable(): bool
    {
        return class_exists(\Dompdf\Dompdf::class);
    }

    /** The HTML given to dompdf (public so it can be checked without the library). */
    public function html(MembershipCertificate $certificate, ?CardSetting $setting = null): string
    {
        $setting ??= CardSetting::current();

        return view('pdf.membership-certificate', $this->data($certificate, $setting))->render();
    }

    /**
     * @throws PdfNotReady when dompdf is missing or the PDF cannot be made
     */
    public function render(MembershipCertificate $certificate): string
    {
        if (! $this->isAvailable()) {
            throw new PdfNotReady('The PDF library is not installed yet, so this certificate cannot be made. Run: composer require dompdf/dompdf');
        }

        try {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', false);      // images are embedded, nothing is fetched from the internet
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isFontSubsettingEnabled', true);
            $options->set('chroot', [resource_path('pdf')]);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($this->html($certificate));
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return $dompdf->output();
        } catch (Throwable $e) {
            Log::error('Membership certificate PDF failed', ['certificate_id' => $certificate->id, 'error' => $e->getMessage()]);

            throw new PdfNotReady('This certificate could not be made right now. The details are in the log file (storage/logs/laravel.log).');
        }
    }

    /** A ready download response (private, never cached, safe file name). */
    public function response(MembershipCertificate $certificate): Response
    {
        $contents = $this->render($certificate);

        $suffix = preg_replace('/[^A-Za-z0-9._-]/', '', (string) $certificate->certificate_no);
        $filename = 'membership-certificate' . ($suffix !== '' ? '-' . $suffix : '') . '.pdf';

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function data(MembershipCertificate $certificate, CardSetting $setting): array
    {
        $org = OrganizationSetting::current();

        return [
            'orgName' => (string) $org->name,
            'orgAddress' => trim((string) ($org->address ?? '')),
            'logoUri' => $this->fileToDataUri($setting->imagePath('logo') ?? $org->logoFilePath()),
            'stampUri' => $this->fileToDataUri($setting->imagePath('stamp')),
            'memberName' => (string) $certificate->member_name,
            'designation' => trim((string) $certificate->designation),
            'certificateNo' => (string) $certificate->certificate_no,
            'issuedOn' => $certificate->issue_date?->format('F j, Y') ?? '-',
            'validUntil' => $certificate->valid_until ? $certificate->valid_until->format('F j, Y') : 'Lifetime',
            'signatory' => trim((string) $setting->letter_signatory_name),
            'signatoryRole' => trim((string) $setting->letter_signatory_role),
        ];
    }

    private function fileToDataUri(?string $path): string
    {
        if (! $path || ! is_file($path)) {
            return '';
        }

        $info = @getimagesize($path);
        $mime = $info['mime'] ?? '';

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            return '';
        }

        $data = @file_get_contents($path);

        return $data === false ? '' : 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
