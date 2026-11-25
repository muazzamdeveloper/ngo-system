@extends('layouts.admin')

@section('title', $donation->exists ? 'Edit donation' : 'Add donation')

@section('content')
    @php
        $isEdit = $donation->exists;
        $when = old('donated_at', $donation->donated_at?->format('Y-m-d\TH:i'));
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $isEdit ? 'Edit donation ' . $donation->receipt_no : 'Record a donation' }}</h1>
            <p class="page-subtitle">Only completed donations count towards campaign totals.</p>
        </div>
        <a href="{{ route('donations.index') }}" class="btn btn-light">&larr; Back to ledger</a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('donations.update', $donation) : route('donations.store') }}" class="card form-card" novalidate>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="donor_name">Donor name <span class="req">*</span></label>
                <input class="form-input" id="donor_name" name="donor_name" value="{{ old('donor_name', $donation->donor_name) }}" maxlength="150" required>
                @error('donor_name') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="amount">Amount (₹) <span class="req">*</span></label>
                <div class="input-prefix"><span>&#8377;</span>
                    <input class="form-input" id="amount" type="number" step="0.01" min="1" name="amount" value="{{ old('amount', $donation->amount) }}" required>
                </div>
                @error('amount') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="donor_email">Email</label>
                <input class="form-input" id="donor_email" type="email" name="donor_email" value="{{ old('donor_email', $donation->donor_email) }}" maxlength="190">
                @error('donor_email') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="donor_phone">Phone</label>
                <input class="form-input" id="donor_phone" name="donor_phone" value="{{ old('donor_phone', $donation->donor_phone) }}" maxlength="20">
                @error('donor_phone') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="donor_pan">PAN (for 80G receipts)</label>
                <input class="form-input" id="donor_pan" name="donor_pan" value="{{ old('donor_pan', $donation->donor_pan) }}" maxlength="10" style="text-transform:uppercase">
                @error('donor_pan') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="campaign_id">Campaign</label>
                <select class="form-input" id="campaign_id" name="campaign_id">
                    <option value="">General support</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('campaign_id', $donation->campaign_id) === (string) $campaign->id)>{{ $campaign->title }}</option>
                    @endforeach
                </select>
                @error('campaign_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="method">Payment method <span class="req">*</span></label>
                <select class="form-input" id="method" name="method" required>
                    @foreach (\App\Models\Donation::METHODS as $value => $label)
                        <option value="{{ $value }}" @selected(old('method', $donation->method) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('method') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status <span class="req">*</span></label>
                <select class="form-input" id="status" name="status" required>
                    @foreach (\App\Models\Donation::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $donation->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="donated_at">Date &amp; time</label>
                <input class="form-input" id="donated_at" type="datetime-local" name="donated_at" value="{{ $when }}">
                @error('donated_at') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="transaction_ref">Transaction / UTR reference</label>
                <input class="form-input" id="transaction_ref" name="transaction_ref" value="{{ old('transaction_ref', $donation->transaction_ref) }}" maxlength="120">
                @error('transaction_ref') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group span-2">
                <label class="form-label" for="notes">Internal notes</label>
                <textarea class="form-input" id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes', $donation->notes) }}</textarea>
                @error('notes') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">{{ $isEdit ? 'Save changes' : 'Record donation' }}</button>
            <a href="{{ route('donations.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
@endsection
