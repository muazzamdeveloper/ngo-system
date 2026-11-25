@extends('layouts.member')

@section('title', 'Edit my details')

@section('content')
    <h1 class="page-title">Edit my details</h1>

    <div class="card">
        @if ($errors->any())
            <div class="alert alert-danger">Please fix the highlighted fields and try again.</div>
        @endif

        <p class="text-muted">
            Your name, father's name, date of birth, email and Aadhaar can only be changed by the organization.
            Please contact them if any of these is wrong.
        </p>

        <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="mobile">Mobile *</label>
                    <input class="form-input" type="tel" id="mobile" name="mobile"
                           value="{{ old('mobile', $member->mobile) }}" maxlength="20" required>
                    @error('mobile') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="occupation">Occupation</label>
                    <input class="form-input" type="text" id="occupation" name="occupation"
                           value="{{ old('occupation', $member->occupation) }}" maxlength="255">
                    @error('occupation') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group form-grid-full">
                    <label class="form-label" for="address">Address *</label>
                    <textarea class="form-input" id="address" name="address" rows="3"
                              maxlength="500" required>{{ old('address', $member->address) }}</textarea>
                    @error('address') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="city">City *</label>
                    <input class="form-input" type="text" id="city" name="city"
                           value="{{ old('city', $member->city) }}" maxlength="100" required>
                    @error('city') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="state">State *</label>
                    <input class="form-input" type="text" id="state" name="state"
                           value="{{ old('state', $member->state) }}" maxlength="100" required>
                    @error('state') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="pincode">Pincode *</label>
                    <input class="form-input" type="text" id="pincode" name="pincode" inputmode="numeric"
                           value="{{ old('pincode', $member->pincode) }}" maxlength="6" required>
                    @error('pincode') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="photo">Replace photo</label>
                    @if ($member->photo_path)
                        <img class="member-photo member-photo-small" src="{{ route('account.photo') }}" alt="Current photo">
                    @endif
                    <input class="form-input" type="file" id="photo" name="photo"
                           accept="image/jpeg,image/png,image/webp">
                    <div class="form-hint">JPG, PNG or WebP, up to 2 MB.</div>
                    @if ($member->photo_path)
                        <label class="form-check">
                            <input type="checkbox" name="remove_photo" value="1">
                            <span>Remove current photo</span>
                        </label>
                    @endif
                    @error('photo') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Save</button>
                <a href="{{ route('account.home') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection
