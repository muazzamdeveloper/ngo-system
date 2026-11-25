<?php

namespace App\Http\Controllers;

use App\Models\CardSetting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The payment QR is the one image that must be visible to visitors (it sits above
 * the public application form). It is streamed from the private disk, so no other
 * stored file becomes reachable.
 */
class PaymentQrController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $path = CardSetting::current()->payment_qr_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'public, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
