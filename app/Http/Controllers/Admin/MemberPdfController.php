<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PdfNotReady;
use App\Http\Controllers\Controller;
use App\Models\CardSetting;
use App\Models\Member;
use App\Services\CardPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/** Staff download of a member's ID card or joining letter (approved members only). */
class MemberPdfController extends Controller
{
    /** True when this staff member may open the ID card (it can carry the full Aadhaar number). */
    public static function canOpenIdCard(Request $request): bool
    {
        return CardSetting::current()->card_aadhaar_display !== 'full'
            || $request->user()->can('members.view_aadhaar');
    }

    public function download(Request $request, Member $member, string $document, CardPdfService $pdf): Response|RedirectResponse
    {
        abort_unless($member->isApproved() && in_array($document, CardPdfService::DOCUMENTS, true), 404);

        // A card that prints the full Aadhaar is as sensitive as the Aadhaar itself.
        abort_if($document === CardPdfService::ID_CARD && ! self::canOpenIdCard($request), 403);

        try {
            $response = $pdf->response($document, $member);
        } catch (PdfNotReady $e) {
            return back()->with('error', $e->getMessage());
        }

        Log::info('Member document downloaded', ['member_id' => $member->id, 'document' => $document, 'user_id' => $request->user()->id]);

        return $response;
    }
}
