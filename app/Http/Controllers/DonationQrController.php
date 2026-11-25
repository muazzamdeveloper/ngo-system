<?php

namespace App\Http\Controllers;

use App\Models\PaymentSetting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The donation QR is public (it is shown on the donate page). Streamed from the private disk. */
class DonationQrController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $path = PaymentSetting::current()->donation_qr_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'public, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
