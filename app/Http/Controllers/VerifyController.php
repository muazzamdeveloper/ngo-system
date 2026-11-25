<?php

namespace App\Http\Controllers;

use App\Exceptions\PdfNotReady;
use App\Models\Member;
use App\Rules\AadhaarNumber;
use App\Services\CardPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Public membership check by Aadhaar number.
 *
 * Whatever the reason (no such number, pending, rejected, blocked, deactivated) the
 * visitor sees ONE message, so nobody can use this page to find out who is a member.
 * A successful check shows no name and no Aadhaar, only short-lived download links.
 */
class VerifyController extends Controller
{
    public const NOT_VERIFIED_MESSAGE = 'We could not verify a membership with this Aadhaar number.';

    private const MAX_ATTEMPTS = 10;
    private const WINDOW_SECONDS = 900; // 15 minutes
    public const LINK_MINUTES = 15;

    public function create(): View
    {
        return view('verify.create');
    }

    public function check(Request $request): Response|RedirectResponse
    {
        $key = 'verify:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            return back()->withErrors(['form' => "Too many attempts. Please try again in {$minutes} minute(s)."]);
        }

        // Every attempt counts, successful or not.
        RateLimiter::hit($key, self::WINDOW_SECONDS);

        $validated = $request->validate(['aadhaar' => ['required', 'string', 'max:20', new AadhaarNumber()]]);

        $member = Member::findByAadhaar($validated['aadhaar']);

        if ($member === null || ! $member->isApproved()) {
            return back()->withErrors(['form' => self::NOT_VERIFIED_MESSAGE]);
        }

        Log::info('Membership verified', ['member_id' => $member->id, 'ip' => $request->ip()]);

        $links = [];
        foreach (CardPdfService::DOCUMENTS as $document) {
            $links[$document] = URL::temporarySignedRoute(
                'verify.download',
                now()->addMinutes(self::LINK_MINUTES),
                ['member' => $member->id, 'document' => $document],
            );
        }

        return response()
            ->view('verify.result', ['links' => $links, 'minutes' => self::LINK_MINUTES])
            ->header('Cache-Control', 'no-store, private');
    }

    public function download(Member $member, string $document, CardPdfService $pdf): Response
    {
        // The status may have changed since the link was made.
        abort_unless($member->isApproved() && in_array($document, CardPdfService::DOCUMENTS, true), 404);

        try {
            return $pdf->response($document, $member);
        } catch (PdfNotReady) {
            return response()
                ->view('verify.unavailable', [], 503)
                ->header('Cache-Control', 'no-store, private');
        }
    }
}
