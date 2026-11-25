@extends('layouts.admin')

@section('title', 'Payment settings')

@section('content')
    <h1 class="page-title"><x-admin-icon name="card" /> Payment settings</h1>

    <p class="text-muted settings-intro">
        There are two separate payments. The <strong>application fee</strong> is paid only by QR code on the application
        form. <strong>Donations</strong> have their own QR here, and online payment (Razorpay and others) is added to
        this page in the next module.
    </p>

    @if ($errors->any())
        <div class="alert alert-danger">Please fix the highlighted fields and try again.</div>
    @endif

    {{-- Application fee: read only, managed in Card Settings --}}
    <div class="card settings-section">
        <h2>Application fee (membership form)</h2>
        <p class="text-muted" style="margin-bottom: 0.5rem;">
            Paid by QR code only. The QR and the fee amount are managed in Card Settings.
        </p>
        <p style="margin-bottom: 0.75rem;">
            Fee: <strong>{{ $feeLabel ?? 'not set' }}</strong> &middot;
            QR code: <strong>{{ $hasFeeQr ? 'uploaded' : 'not uploaded' }}</strong>
        </p>
        @can('card_settings.manage')
            <a href="{{ route('card-settings.edit') }}" class="btn btn-light btn-small">Open Card Settings</a>
        @endcan
    </div>

    {{-- Donations --}}
    <form method="POST" action="{{ route('payment-settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card settings-section">
            <h2>Donations</h2>
            <p class="text-muted">This QR is shown on the donate page. It is separate from the application fee QR.</p>

            <div class="form-group">
                <label class="form-label" for="donation_qr">Donation QR code</label>

                @if ($setting->hasDonationQr())
                    <img class="image-preview" alt="Donation QR code"
                         src="{{ route('payment-settings.donation-qr') }}?v={{ $setting->updated_at?->timestamp }}">
                @endif

                <input class="form-input" type="file" id="donation_qr" name="donation_qr" accept="image/jpeg,image/png">
                <div class="form-hint">JPG or PNG, up to 2 MB, at most 4000 x 4000 pixels.</div>

                @if ($setting->hasDonationQr())
                    <label class="form-check">
                        <input type="checkbox" name="remove_donation_qr" value="1" @checked(old('remove_donation_qr'))>
                        <span>Remove current QR</span>
                    </label>
                @endif

                @error('donation_qr') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="donation_upi_id">UPI ID (optional)</label>
                <input class="form-input" type="text" id="donation_upi_id" name="donation_upi_id" maxlength="100"
                       value="{{ old('donation_upi_id', $setting->donation_upi_id) }}" placeholder="name@bank">
                <div class="form-hint">Shown next to the QR so donors can also pay by typing the UPI ID.</div>
                @error('donation_upi_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="donation_instructions">Note for donors (optional)</label>
                <textarea class="form-input" id="donation_instructions" name="donation_instructions" rows="3" maxlength="500">{{ old('donation_instructions', $setting->donation_instructions) }}</textarea>
                <div class="form-hint">For example: where to send the payment screenshot. Up to 500 characters.</div>
                @error('donation_instructions') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Save payment settings</button>
        </div>
    </form>
@endsection
