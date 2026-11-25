@extends('layouts.site')
@section('title', 'Volunteer')
@section('content')
    <section class="page-intro"><h1>Become a volunteer</h1><p>Tell us a little about yourself. A coordinator will contact you.</p></section>
    <form method="POST" action="{{ route('volunteer.store') }}" class="card-public form-public donate-grid-single" novalidate>
        @csrf
        <div class="hp-field" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-grid">
            <div class="form-group"><label class="form-label" for="full_name">Full name <span class="req">*</span></label>
                <input class="form-input" id="full_name" name="full_name" maxlength="150" value="{{ old('full_name') }}" required>
                @error('full_name') <div class="form-error">{{ $message }}</div> @enderror</div>
            <div class="form-group"><label class="form-label" for="mobile">Mobile <span class="req">*</span></label>
                <input class="form-input" id="mobile" name="mobile" maxlength="20" value="{{ old('mobile') }}" required>
                @error('mobile') <div class="form-error">{{ $message }}</div> @enderror</div>
            <div class="form-group"><label class="form-label" for="email">Email</label>
                <input class="form-input" id="email" type="email" name="email" maxlength="190" value="{{ old('email') }}">
                @error('email') <div class="form-error">{{ $message }}</div> @enderror</div>
            <div class="form-group"><label class="form-label" for="city">City</label>
                <input class="form-input" id="city" name="city" maxlength="120" value="{{ old('city') }}"></div>
            <div class="form-group"><label class="form-label" for="state">State</label>
                <input class="form-input" id="state" name="state" maxlength="120" value="{{ old('state') }}"></div>
            <div class="form-group"><label class="form-label" for="interests">Areas you can help with</label>
                <input class="form-input" id="interests" name="interests" maxlength="500" value="{{ old('interests') }}" placeholder="Education, health camps, events…"></div>
        </div>
        <div class="form-group"><label class="form-label" for="message">Message</label>
            <textarea class="form-input" id="message" name="message" rows="3" maxlength="2000">{{ old('message') }}</textarea></div>
        <button type="submit" class="btn">Send my details</button>
    </form>
@endsection
