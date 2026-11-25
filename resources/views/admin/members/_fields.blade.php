{{-- Shared by the add and edit forms. Expects: $member (Member or null), $genders, $editing --}}
<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="full_name">Full name *</label>
        <input class="form-input" type="text" id="full_name" name="full_name"
               value="{{ old('full_name', $member?->full_name) }}" maxlength="255" required>
        @error('full_name') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="father_name">Father's name *</label>
        <input class="form-input" type="text" id="father_name" name="father_name"
               value="{{ old('father_name', $member?->father_name) }}" maxlength="255" required>
        @error('father_name') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="date_of_birth">Date of birth *</label>
        <input class="form-input" type="date" id="date_of_birth" name="date_of_birth"
               value="{{ old('date_of_birth', $member?->date_of_birth?->format('Y-m-d')) }}"
               max="{{ now()->subDay()->toDateString() }}" required>
        @error('date_of_birth') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="gender">Gender *</label>
        <select class="form-input" id="gender" name="gender" required>
            <option value="">Select</option>
            @foreach ($genders as $gender)
                <option value="{{ $gender }}" @selected(old('gender', $member?->gender) === $gender)>{{ ucfirst($gender) }}</option>
            @endforeach
        </select>
        @error('gender') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="blood_group">Blood group</label>
        <select class="form-input" id="blood_group" name="blood_group">
            <option value="">Select</option>
            @foreach (\App\Models\Member::BLOOD_GROUPS as $group)
                <option value="{{ $group }}" @selected(old('blood_group', $member?->blood_group) === $group)>{{ $group }}</option>
            @endforeach
        </select>
        @error('blood_group') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="mobile">Mobile *</label>
        <input class="form-input" type="tel" id="mobile" name="mobile"
               value="{{ old('mobile', $member?->mobile) }}" maxlength="20" required>
        @error('mobile') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="whatsapp">WhatsApp number</label>
        <input class="form-input" type="tel" id="whatsapp" name="whatsapp"
               value="{{ old('whatsapp', $member?->whatsapp) }}" maxlength="20">
        @error('whatsapp') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-input" type="email" id="email" name="email"
               value="{{ old('email', $member?->email) }}" maxlength="255">
        <div class="form-hint">Needed later if this member will get a login account.</div>
        @error('email') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group form-grid-full">
        <label class="form-label" for="address">Address *</label>
        <textarea class="form-input" id="address" name="address" rows="3"
                  maxlength="500" required>{{ old('address', $member?->address) }}</textarea>
        @error('address') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="city">City *</label>
        <input class="form-input" type="text" id="city" name="city"
               value="{{ old('city', $member?->city) }}" maxlength="100" required>
        @error('city') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="district">District</label>
        <input class="form-input" type="text" id="district" name="district"
               value="{{ old('district', $member?->district) }}" maxlength="100">
        @error('district') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="state">State *</label>
        <input class="form-input" type="text" id="state" name="state"
               value="{{ old('state', $member?->state) }}" maxlength="100" required>
        @error('state') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="pincode">Pincode *</label>
        <input class="form-input" type="text" id="pincode" name="pincode" inputmode="numeric"
               value="{{ old('pincode', $member?->pincode) }}" maxlength="6" required>
        @error('pincode') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="work_area">Work area</label>
        <input class="form-input" type="text" id="work_area" name="work_area"
               value="{{ old('work_area', $member?->work_area) }}" maxlength="255">
        @error('work_area') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="occupation">Occupation</label>
        <input class="form-input" type="text" id="occupation" name="occupation"
               value="{{ old('occupation', $member?->occupation) }}" maxlength="255">
        @error('occupation') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="aadhaar">{{ $editing ? 'Replace Aadhaar number' : 'Aadhaar number' }}</label>
        <input class="form-input" type="text" id="aadhaar" name="aadhaar" inputmode="numeric"
               value="{{ old('aadhaar') }}" maxlength="14" autocomplete="off">
        <div class="form-hint">
            @if ($editing)
                Current: {{ $member->maskedAadhaar() }}. Leave blank to keep it.
            @else
                Optional. Stored encrypted and shown masked everywhere.
            @endif
        </div>
        @error('aadhaar') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="photo">{{ $editing ? 'Replace photo' : 'Photo' }}</label>
        @if ($editing && $member->photo_path)
            <img class="member-photo member-photo-small" src="{{ route('members.photo', $member) }}" alt="Current photo">
        @endif
        <input class="form-input" type="file" id="photo" name="photo"
               accept="image/jpeg,image/png,image/webp">
        <div class="form-hint">JPG, PNG or WebP, up to 2 MB.</div>
        @if ($editing && $member->photo_path)
            <label class="form-check">
                <input type="checkbox" name="remove_photo" value="1">
                <span>Remove current photo</span>
            </label>
        @endif
        @error('photo') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group form-grid-full"><h3 class="section-title">Documents (optional)</h3></div>

    @foreach (\App\Models\Member::DOCUMENT_LABELS as $field => $label)
        @php($column = \App\Models\Member::DOCUMENT_COLUMNS[$field])
        @php($isAadhaar = str_starts_with($field, 'aadhaar_'))
        <div class="form-group">
            <label class="form-label" for="{{ $field }}">{{ $editing ? 'Replace ' . strtolower($label) : $label }}</label>

            @if ($editing && $member->{$column})
                <div class="form-hint">
                    Current file:
                    @if (! $isAadhaar || auth()->user()->can('members.view_aadhaar'))
                        <a href="{{ route('members.document', [$member, str_replace('_', '-', $field)]) }}" target="_blank" rel="noopener">view</a>
                    @else
                        uploaded
                    @endif
                </div>
            @endif

            <input class="form-input" type="file" id="{{ $field }}" name="{{ $field }}"
                   accept="{{ $field === 'resume' ? 'application/pdf' : 'image/jpeg,image/png,image/webp' }}">
            <div class="form-hint">{{ $field === 'resume' ? 'PDF, up to 10 MB.' : 'JPG, PNG or WebP, up to 5 MB.' }}</div>

            @if ($editing && $member->{$column})
                <label class="form-check">
                    <input type="checkbox" name="remove_{{ $field }}" value="1">
                    <span>Remove current file</span>
                </label>
            @endif

            @error($field) <div class="form-error">{{ $message }}</div> @enderror
        </div>
    @endforeach
</div>
