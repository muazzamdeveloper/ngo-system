<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardSetting;
use App\Models\PaymentSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/** Donation QR and related texts. (The application-fee QR is managed in Card Settings.) */
class PaymentSettingController extends Controller
{
    public function edit(): View
    {
        $card = CardSetting::current();

        return view('admin.payment-settings.edit', [
            'setting' => PaymentSetting::current(),
            'feeLabel' => $card->feeLabel(),
            'hasFeeQr' => filled($card->payment_qr_path),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'donation_qr' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_donation_qr' => ['nullable', 'boolean'],
            'donation_upi_id' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9.\-_]{2,}@[A-Za-z][A-Za-z0-9]{1,}$/'],
            'donation_instructions' => ['nullable', 'string', 'max:500'],
        ], [
            'donation_qr.mimes' => 'The donation QR must be a JPG or PNG image.',
            'donation_qr.max' => 'The donation QR must not be larger than 2 MB.',
            'donation_qr.dimensions' => 'The donation QR must not be larger than 4000 x 4000 pixels.',
            'donation_upi_id.regex' => 'Enter a valid UPI ID, for example name@bank.',
        ]);

        $setting = PaymentSetting::query()->first() ?? new PaymentSetting();

        $setting->fill([
            'donation_upi_id' => filled($data['donation_upi_id'] ?? null) ? trim($data['donation_upi_id']) : null,
            'donation_instructions' => filled($data['donation_instructions'] ?? null) ? trim($data['donation_instructions']) : null,
        ]);

        $newFile = null;
        $oldFile = null;

        if ($request->hasFile('donation_qr')) {
            $newFile = $request->file('donation_qr')->store('payment-settings', 'local');
            $oldFile = $setting->donation_qr_path;
            $setting->donation_qr_path = $newFile;
        } elseif ($request->boolean('remove_donation_qr') && $setting->donation_qr_path) {
            $oldFile = $setting->donation_qr_path;
            $setting->donation_qr_path = null;
        }

        try {
            DB::transaction(fn () => $setting->save());
        } catch (Throwable $e) {
            if ($newFile) {
                Storage::disk('local')->delete($newFile);
            }

            throw $e;
        }

        if ($oldFile) {
            Storage::disk('local')->delete($oldFile);
        }

        return redirect()->route('payment-settings.edit')->with('success', 'Payment settings saved.');
    }

    /** Preview of the donation QR inside the admin panel. */
    public function donationQr(): StreamedResponse
    {
        $path = PaymentSetting::current()->donation_qr_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
