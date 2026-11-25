<?php

namespace App\Http\Controllers\Account;

use App\Exceptions\PdfNotReady;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\CardPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** A logged-in member downloads their OWN ID card or joining letter. No member id is ever in the URL. */
class DocumentController extends Controller
{
    public function download(Request $request, string $document, CardPdfService $pdf): Response|RedirectResponse
    {
        abort_unless($request->user()->can('portal.download_documents'), 403, 'Downloading documents is turned off for your account.');

        $member = Member::where('user_id', $request->user()->id)->first();

        abort_unless($member && $member->isApproved() && in_array($document, CardPdfService::DOCUMENTS, true), 404);

        try {
            return $pdf->response($document, $member);
        } catch (PdfNotReady) {
            return back()->with('error', 'This document is not available right now. Please contact the organization.');
        }
        // Members see only the generic message above; the reason is written to the log for staff.
    }
}
