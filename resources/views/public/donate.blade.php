@extends('layouts.site')
@section('title', 'Donate')
@section('content')
    <section class="page-intro">
        <span class="page-eyebrow">Make an impact</span>
        <h1>Make a donation</h1>
        <p>Pay by UPI or bank transfer, then tell us the amount and reference. Our team confirms every donation.</p>
    </section>

    <div class="donate-layout">
        <form method="POST" action="{{ route('donate.store') }}" class="card-public form-public donate-form" novalidate>
            @csrf
            <div class="hp-field" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

            <div class="form-group">
                <label class="form-label" for="campaign_id">Supporting campaign</label>
                <select class="form-input" id="campaign_id" name="campaign_id">
                    <option value="">General support (where most needed)</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) ($selected ?? old('campaign_id')) === (string) $campaign->id)>{{ $campaign->title }}</option>
                    @endforeach
                </select>
            </div>

            <fieldset class="amount-fieldset">
                <legend class="form-label">Choose an amount</legend>
                <div class="amount-chips" role="group" aria-label="Quick amounts">
                    @foreach ([500, 1000, 2000, 5000, 10000, 21000] as $quick)
                        <button type="button" class="amount-chip" data-amount="{{ $quick }}">₹{{ number_format($quick) }}</button>
                    @endforeach
                </div>
                <div class="form-group">
                    <label class="form-label" for="amount">Or enter an amount (₹) <span class="req">*</span></label>
                    <input class="form-input" id="amount" type="number" min="1" step="1" name="amount" value="{{ old('amount') }}" required>
                    @error('amount') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </fieldset>

            <div class="form-grid">
                <div class="form-group"><label class="form-label" for="donor_name">Full name <span class="req">*</span></label>
                    <input class="form-input" id="donor_name" name="donor_name" maxlength="150" value="{{ old('donor_name') }}" required>
                    @error('donor_name') <div class="form-error">{{ $message }}</div> @enderror</div>
                <div class="form-group"><label class="form-label" for="donor_phone">Mobile <span class="req">*</span></label>
                    <input class="form-input" id="donor_phone" name="donor_phone" maxlength="20" value="{{ old('donor_phone') }}" required>
                    @error('donor_phone') <div class="form-error">{{ $message }}</div> @enderror</div>
                <div class="form-group"><label class="form-label" for="donor_email">Email</label>
                    <input class="form-input" id="donor_email" type="email" name="donor_email" maxlength="190" value="{{ old('donor_email') }}">
                    @error('donor_email') <div class="form-error">{{ $message }}</div> @enderror</div>
                <div class="form-group"><label class="form-label" for="method">Paid by <span class="req">*</span></label>
                    <select class="form-input" id="method" name="method" required>
                        <option value="upi" @selected(old('method', 'upi') === 'upi')>UPI</option>
                        <option value="bank" @selected(old('method') === 'bank')>Bank transfer</option>
                    </select></div>
                <div class="form-group span-all"><label class="form-label" for="transaction_ref">Transaction / UTR reference</label>
                    <input class="form-input" id="transaction_ref" name="transaction_ref" maxlength="120" value="{{ old('transaction_ref') }}">
                    <span class="form-hint">Enter the reference you received from your bank or UPI app.</span></div>
            </div>

            <div class="form-group"><label class="form-label" for="notes">Message (optional)</label>
                <textarea class="form-input" id="notes" name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea></div>

            <button type="submit" class="btn btn-block donate-submit">Submit donation details</button>
        </form>

        <aside class="card-public donate-aside" aria-labelledby="donate-how">
            <h2 id="donate-how">Pay with UPI</h2>
            @if ($payment->hasDonationQr())
                <img class="donate-qr" src="{{ route('donation-qr') }}" alt="Donation UPI QR code">
            @endif
            @if (filled($payment->donation_upi_id))
                <p class="donate-upi">UPI ID: <strong>{{ $payment->donation_upi_id }}</strong></p>
            @endif
            @if (filled($payment->donation_instructions))
                <p class="small">{{ $payment->donation_instructions }}</p>
            @endif
            <p class="small muted">After paying, fill in the form and add the transaction reference so we can confirm your donation quickly.</p>
        </aside>
    </div>

    <script>
        (function () {
            var amount = document.getElementById('amount');
            document.querySelectorAll('[data-amount]').forEach(function (button) {
                button.addEventListener('click', function () {
                    amount.value = button.getAttribute('data-amount');
                    document.querySelectorAll('.amount-chip').forEach(function (chip) { chip.classList.toggle('is-selected', chip === button); });
                });
            });
        })();
    </script>
@endsection
