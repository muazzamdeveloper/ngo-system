<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PdfNotReady;
use App\Http\Controllers\Controller;
use App\Models\MembershipCertificate;
use App\Services\MembershipCertificatePdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/** Staff download of a membership certificate as a PDF (needs the "Membership certificates" permission). */
class MembershipCertificatePdfController extends Controller
{
    public function download(Request $request, MembershipCertificate $certificate, MembershipCertificatePdf $pdf): Response|RedirectResponse
    {
        try {
            $response = $pdf->response($certificate);
        } catch (PdfNotReady $e) {
            return back()->with('error', $e->getMessage());
        }

        Log::info('Membership certificate downloaded', ['certificate_id' => $certificate->id, 'user_id' => $request->user()->id]);

        return $response;
    }
}
