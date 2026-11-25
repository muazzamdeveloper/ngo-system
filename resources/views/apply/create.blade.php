@extends('layouts.site')

@section('title', 'Membership application')

@section('hero')
    <span class="hero-pill">{{ $setting->card_heading }} ID card application</span>
    <h1>{{ $setting->card_designation }} Application Form</h1>
    <p class="hero-text">
        Fill in your details, upload your documents{{ $hasQr || $feeLabel ? ' and pay the application fee' : '' }}.
        Your ID card and joining letter are ready to download once the admin approves.
    </p>
    <ul class="hero-chips">
        <li><x-icon name="check" /> ID card and joining letter after approval</li>
        <li><x-icon name="check" /> Check status anytime with your Aadhaar number</li>
    </ul>
@endsection

@section('content')
    @if ($errors->has('form'))
        <div class="alert alert-danger">{{ $errors->first('form') }}</div>
    @elseif ($errors->any())
        <div class="alert alert-danger">Please fix the highlighted fields and try again. Files must be chosen again.</div>
    @endif

    @if ($feeLabel || $hasQr)
        <section class="site-card">
            <div class="section-head">
                <span class="section-icon"><x-icon name="qr" /></span>
                <div>
                    <h2>Payment / Application Fee</h2>
                    <p>Scan the QR code to complete your payment.</p>
                </div>
            </div>

            <div class="pay-layout">
                <div class="pay-qr">
                    @if ($hasQr)
                        <div class="pay-qr-box">
                            <img src="{{ route('payment-qr') }}?v={{ $setting->updated_at?->timestamp }}" alt="Payment QR code">
                        </div>
                    @endif

                    @if ($feeLabel)
                        <p class="pay-fee">{{ \Illuminate\Support\Str::before($feeLabel, ': ') }}: <strong>{{ \Illuminate\Support\Str::after($feeLabel, ': ') }}</strong></p>
                    @endif
                </div>

                @if ($hasQr)
                    <ol class="pay-steps">
                        <li><span>1</span> Scan the QR code and pay the application fee.</li>
                        <li><span>2</span> Take a screenshot of the successful payment.</li>
                        <li><span>3</span> Upload it with your other documents below.</li>
                    </ol>
                @endif
            </div>
        </section>
    @endif

    <form method="POST" action="{{ route('apply.store') }}" enctype="multipart/form-data" id="apply-form" autocomplete="off" novalidate>
        @csrf

        {{-- Trap for simple bots. Real visitors never see or fill this. --}}
        <div class="hp-field" aria-hidden="true">
            <label for="website">Leave this empty</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <section class="site-card">
            <div class="section-head">
                <span class="section-icon"><x-icon name="user" /></span>
                <div>
                    <h2>Personal Details</h2>
                    <p>Tell us about yourself.</p>
                </div>
            </div>

            <div class="site-grid">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name <span class="req">*</span></label>
                    <input class="form-input" type="text" id="first_name" name="first_name" maxlength="100"
                           placeholder="Enter first name" autocomplete="given-name" value="{{ old('first_name') }}" required>
                    @error('first_name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name <span class="req">*</span></label>
                    <input class="form-input" type="text" id="last_name" name="last_name" maxlength="100"
                           placeholder="Enter last name" autocomplete="family-name" value="{{ old('last_name') }}" required>
                    @error('last_name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="gender">Gender <span class="req">*</span></label>
                    <select class="form-input" id="gender" name="gender" required>
                        <option value="">Select gender</option>
                        @foreach ($genders as $gender)
                            <option value="{{ $gender }}" @selected(old('gender') === $gender)>{{ ucfirst($gender) }}</option>
                        @endforeach
                    </select>
                    @error('gender') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="father_name">Father Name <span class="opt">(Optional)</span></label>
                    <input class="form-input" type="text" id="father_name" name="father_name" maxlength="255"
                           placeholder="Enter father's name" value="{{ old('father_name') }}">
                    @error('father_name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="date_of_birth">Date of Birth <span class="req">*</span></label>
                    <input class="form-input" type="date" id="date_of_birth" name="date_of_birth" required
                           min="1900-01-02" max="{{ now()->subDay()->format('Y-m-d') }}" value="{{ old('date_of_birth') }}">
                    @error('date_of_birth') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="blood_group">Blood Group <span class="req">*</span></label>
                    <select class="form-input" id="blood_group" name="blood_group" required>
                        <option value="">Select blood group</option>
                        @foreach ($bloodGroups as $group)
                            <option value="{{ $group }}" @selected(old('blood_group') === $group)>{{ $group }}</option>
                        @endforeach
                    </select>
                    @error('blood_group') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>

        <section class="site-card">
            <div class="section-head">
                <span class="section-icon"><x-icon name="phone" /></span>
                <div>
                    <h2>Contact</h2>
                    <p>How we can reach you.</p>
                </div>
            </div>

            <div class="site-grid">
                <div class="form-group">
                    <label class="form-label" for="mobile">Mobile No. <span class="req">*</span></label>
                    <input class="form-input" type="tel" id="mobile" name="mobile" inputmode="numeric" maxlength="16"
                           placeholder="Enter mobile number" autocomplete="tel" value="{{ old('mobile') }}" required>
                    @error('mobile') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="whatsapp">WhatsApp No. <span class="opt">(Optional)</span></label>
                    <input class="form-input" type="tel" id="whatsapp" name="whatsapp" inputmode="numeric" maxlength="16"
                           placeholder="Enter WhatsApp number" value="{{ old('whatsapp') }}">
                    @error('whatsapp') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email <span class="req">*</span></label>
                    <input class="form-input" type="email" id="email" name="email" maxlength="255"
                           placeholder="name@example.com" autocomplete="email" value="{{ old('email') }}" required>
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>

        <section class="site-card">
            <div class="section-head">
                <span class="section-icon"><x-icon name="id" /></span>
                <div>
                    <h2>Identity and Address</h2>
                    <p>Used to print your ID card.</p>
                </div>
            </div>

            <div class="site-grid">
                <div class="form-group">
                    <label class="form-label" for="aadhaar">Aadhaar Number <span class="req">*</span></label>
                    <input class="form-input" type="text" id="aadhaar" name="aadhaar" inputmode="numeric" maxlength="20"
                           placeholder="12-digit Aadhaar number (spaces or dashes allowed)" autocomplete="off" required>
                    <div class="field-hint">You will use this to check your application status.</div>
                    @error('aadhaar') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="pincode">Pin Code <span class="opt">(Optional)</span></label>
                    <input class="form-input" type="text" id="pincode" name="pincode" inputmode="numeric" maxlength="6"
                           placeholder="6-digit pin code" value="{{ old('pincode') }}">
                    @error('pincode') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div></div>

                <div class="form-group">
                    <label class="form-label" for="state">State <span class="req">*</span></label>
                    <select class="form-input" id="state" name="state" required>
                        <option value="">Select state / UT</option>
                        @foreach ($states as $state)
                            <option value="{{ $state }}" @selected(old('state') === $state)>{{ $state }}</option>
                        @endforeach
                    </select>
                    @error('state') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="district">District <span class="req">*</span></label>
                    <input class="form-input" type="text" id="district" name="district" maxlength="100"
                           placeholder="Enter district" value="{{ old('district') }}" required>
                    @error('district') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="work_area">Work Area <span class="req">*</span></label>
                    <input class="form-input" type="text" id="work_area" name="work_area" maxlength="21"
                           placeholder="e.g. your town or area" value="{{ old('work_area') }}" required>
                    <div class="field-hint">Maximum 21 characters, printed on your ID card.</div>
                    @error('work_area') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group span-2">
                    <label class="form-label" for="address">Address <span class="req">*</span></label>
                    <textarea class="form-input" id="address" name="address" rows="3" maxlength="500"
                              placeholder="House no., street, village / city" required>{{ old('address') }}</textarea>
                    @error('address') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="card_expiry">Card Expiry Date <span class="opt">(Optional)</span></label>
                    <input class="form-input" type="text" id="card_expiry" value="Set automatically" disabled>
                    <div class="field-hint">Calculated automatically as {{ $setting->card_validity_months }} {{ \Illuminate\Support\Str::plural('month', $setting->card_validity_months) }} from card approval.</div>
                </div>
            </div>
        </section>

        <section class="site-card">
            <div class="section-head">
                <span class="section-icon"><x-icon name="upload" /></span>
                <div>
                    <h2>Upload Documents</h2>
                    <p>Clear, readable photos work best.</p>
                </div>
            </div>

            @php
                $files = [
                    ['aadhaar_front', 'Aadhaar Front', true, 5, 'PNG, JPG, WebP · Max 5MB', 'upload', 'span-1'],
                    ['aadhaar_back', 'Aadhaar Back', true, 5, 'PNG, JPG, WebP · Max 5MB', 'upload', 'span-1'],
                    ['photo', 'Your Photo', true, 2, 'PNG, JPG, WebP · Max 2MB', 'upload', 'span-1'],
                    ['payment_screenshot', 'Payment Screenshot', $hasQr, 5, 'PNG, JPG, WebP · Max 5MB', 'upload', 'span-1'],
                    ['resume', 'Resume', false, 10, 'PDF · Max 10MB', 'file', 'span-2'],
                ];
            @endphp

            <div class="site-grid">
                @foreach ($files as [$name, $label, $required, $maxMb, $hint, $icon, $span])
                    <div class="form-group {{ $span === 'span-2' ? 'span-2' : '' }}">
                        <label class="form-label" for="{{ $name }}">
                            {{ $label }}
                            @if ($required) <span class="req">*</span> @else <span class="opt">(Optional)</span> @endif
                        </label>
                        <label class="dropzone" for="{{ $name }}">
                            <x-icon :name="$icon" />
                            <span class="dz-title" data-default="Upload">Upload</span>
                            <span class="dz-hint">{{ $hint }}</span>
                            <input class="dz-input" type="file" id="{{ $name }}" name="{{ $name }}" data-max-mb="{{ $maxMb }}"
                                   accept="{{ $name === 'resume' ? 'application/pdf' : 'image/jpeg,image/png,image/webp' }}" @required($required)>
                        </label>
                        @error($name) <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                @endforeach
            </div>
        </section>

        <div class="site-notice">{{ $setting->applicationNotice() }}</div>

        <button type="submit" class="btn site-submit">Submit Application <x-icon name="arrow" /></button>
    </form>

    <p class="site-link-line">Already applied? <a href="{{ route('verify.create') }}">Check your application status</a></p>
@endsection
