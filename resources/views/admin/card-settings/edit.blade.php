@extends('layouts.admin')

@section('title', 'Card settings')

@section('content')
    <h1 class="page-title">Card settings</h1>

    <p class="text-muted settings-intro">
        Images, fee, ID card texts and joining letter details. The payment QR appears above the public application form.
        Everything here is used by the ID card and the joining letter PDFs.
    </p>

    @if ($errors->any())
        <div class="alert alert-danger">Please fix the highlighted fields and try again.</div>
    @endif

    @unless ($pdfReady)
        <div class="card settings-section">
            <strong>PDF library not installed yet.</strong>
            <span class="text-muted">Settings can be saved now. Before ID cards and letters can be downloaded, run
                <code>composer require dompdf/dompdf</code> in the project folder.</span>
        </div>
    @endunless

    <form method="POST" action="{{ route('card-settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Images --}}
        <div class="card settings-section">
            <h2>Images</h2>
            <p class="text-muted">JPG or PNG, up to 2 MB each. Use a PNG with a transparent background for the logo and stamp.</p>

            <div class="form-grid">
                @foreach (\App\Models\CardSetting::IMAGE_LABELS as $key => $label)
                    @php($column = \App\Models\CardSetting::IMAGES[$key])
                    <div class="form-group">
                        <label class="form-label" for="{{ $key }}">{{ $label }}</label>

                        @if ($setting->{$column})
                            <img class="image-preview" alt="{{ $label }}"
                                 src="{{ route('card-settings.image', str_replace('_', '-', $key)) }}?v={{ $setting->updated_at?->timestamp }}">
                        @endif

                        <input class="form-input" type="file" id="{{ $key }}" name="{{ $key }}" accept="image/jpeg,image/png">

                        @if ($key === 'payment_qr')
                            <div class="form-hint">Applicants scan this to pay. Shown on the public application page.</div>
                        @elseif ($key === 'website_qr')
                            <div class="form-hint">A QR that contains your website link. It is printed on the ID card.</div>
                        @endif

                        @if ($setting->{$column})
                            <label class="form-check">
                                <input type="checkbox" name="remove_{{ $key }}" value="1" @checked(old('remove_' . $key))>
                                <span>Remove current image</span>
                            </label>
                        @endif

                        @error($key) <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Fee --}}
        <div class="card settings-section">
            <h2>Application fee</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="fee_amount">Fee amount</label>
                    <input class="form-input" type="number" step="0.01" min="0" id="fee_amount" name="fee_amount"
                           value="{{ old('fee_amount', $setting->fee_amount !== null && (float) $setting->fee_amount > 0 ? rtrim(rtrim((string) $setting->fee_amount, '0'), '.') : '') }}">
                    <div class="form-hint">Leave empty to show no fee text above the QR. The QR itself is always shown once uploaded.</div>
                    @error('fee_amount') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="fee_currency">Currency symbol</label>
                    <input class="form-input" type="text" id="fee_currency" name="fee_currency" maxlength="10" required
                           value="{{ old('fee_currency', $setting->fee_currency) }}">
                    <div class="form-hint">For example ₹ or Rs. Shown before the amount.</div>
                    @error('fee_currency') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- ID card --}}
        <div class="card settings-section">
            <h2>ID card</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="card_heading">Card heading</label>
                    <input class="form-input" type="text" id="card_heading" name="card_heading" maxlength="30" required
                           value="{{ old('card_heading', $setting->card_heading) }}">
                    <div class="form-hint">Large word at the top of the card, for example PRESS.</div>
                    @error('card_heading') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="card_designation">Designation on the card</label>
                    <input class="form-input" type="text" id="card_designation" name="card_designation" maxlength="60" required
                           value="{{ old('card_designation', $setting->card_designation) }}">
                    <div class="form-hint">Printed under the name, for example Reporter or Member.</div>
                    @error('card_designation') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="card_validity_months">Validity (months)</label>
                    <input class="form-input" type="number" min="1" max="120" id="card_validity_months" name="card_validity_months" required
                           value="{{ old('card_validity_months', $setting->card_validity_months) }}">
                    <div class="form-hint">The expiry date on the card is the approval date plus this many months.</div>
                    @error('card_validity_months') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="card_aadhaar_display">Aadhaar on the card</label>
                    <select class="form-input" id="card_aadhaar_display" name="card_aadhaar_display" required>
                        @foreach (\App\Models\CardSetting::AADHAAR_DISPLAY as $value => $label)
                            <option value="{{ $value }}" @selected(old('card_aadhaar_display', $setting->card_aadhaar_display) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('card_aadhaar_display') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="udyam_number">Udyam number</label>
                    <input class="form-input" type="text" id="udyam_number" name="udyam_number" maxlength="50"
                           value="{{ old('udyam_number', $setting->udyam_number) }}" placeholder="UDYAM-XX-00-0000000">
                    <div class="form-hint">One number, printed on every ID card and joining letter.</div>
                    @error('udyam_number') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="website_url">Website link</label>
                    <input class="form-input" type="url" id="website_url" name="website_url" maxlength="255"
                           value="{{ old('website_url', $setting->website_url) }}" placeholder="https://example.com">
                    <div class="form-hint">Shown at the bottom of the card back.</div>
                    @error('website_url') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="application_notice">Note under the application form</label>
                    <textarea class="form-input" id="application_notice" name="application_notice" rows="3" maxlength="500">{{ old('application_notice', $setting->application_notice) }}</textarea>
                    <div class="form-hint">Shown in the yellow box above the Submit button on the public form. Leave empty to use the default Hindi note.</div>
                    @error('application_notice') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group form-grid-full">
                    <label class="form-label" for="card_terms">Terms and conditions (card back)</label>
                    <textarea class="form-input" id="card_terms" name="card_terms" rows="7" required>{{ old('card_terms', $setting->card_terms) }}</textarea>
                    <div class="form-hint">One term per line, up to 10 lines.</div>
                    @error('card_terms') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- Joining letter --}}
        <div class="card settings-section">
            <h2>Joining letter</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="letter_site_title">Site or company title</label>
                    <input class="form-input" type="text" id="letter_site_title" name="letter_site_title" maxlength="100"
                           value="{{ old('letter_site_title', $setting->letter_site_title) }}">
                    <div class="form-hint">Empty uses the organization name.</div>
                    @error('letter_site_title') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="letter_signatory_name">Signatory name</label>
                    <input class="form-input" type="text" id="letter_signatory_name" name="letter_signatory_name" maxlength="100"
                           value="{{ old('letter_signatory_name', $setting->letter_signatory_name) }}">
                    @error('letter_signatory_name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="letter_signatory_role">Signatory title</label>
                    <input class="form-input" type="text" id="letter_signatory_role" name="letter_signatory_role" maxlength="60" required
                           value="{{ old('letter_signatory_role', $setting->letter_signatory_role) }}">
                    @error('letter_signatory_role') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="letter_phone">Phone number</label>
                    <input class="form-input" type="text" id="letter_phone" name="letter_phone" maxlength="30"
                           value="{{ old('letter_phone', $setting->letter_phone) }}">
                    @error('letter_phone') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="letter_email">Footer email</label>
                    <input class="form-input" type="email" id="letter_email" name="letter_email" maxlength="255"
                           value="{{ old('letter_email', $setting->letter_email) }}">
                    @error('letter_email') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group form-grid-full">
                    <label class="form-label" for="letter_address">Company address</label>
                    <textarea class="form-input" id="letter_address" name="letter_address" rows="3" maxlength="300">{{ old('letter_address', $setting->letter_address) }}</textarea>
                    @error('letter_address') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="letter_date">Letter date</label>
                    <input class="form-input" type="date" id="letter_date" name="letter_date"
                           value="{{ old('letter_date', $setting->letter_date?->format('Y-m-d')) }}">
                    <div class="form-hint">Empty uses the day the letter is generated.</div>
                    @error('letter_date') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="letter_joining_date">Official joining date</label>
                    <input class="form-input" type="date" id="letter_joining_date" name="letter_joining_date"
                           value="{{ old('letter_joining_date', $setting->letter_joining_date?->format('Y-m-d')) }}">
                    <div class="form-hint">Empty uses the member's approval date.</div>
                    @error('letter_joining_date') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group form-grid-full">
                    <label class="form-label" for="letter_template">Letter text</label>
                    <textarea class="form-input letter-template" id="letter_template" name="letter_template" rows="16" required>{{ old('letter_template', $setting->letter_template) }}</textarea>
                    <div class="form-hint">
                        Simple formatting only: &lt;p&gt;, &lt;br&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;u&gt;, &lt;ol&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;h3&gt;, &lt;h4&gt;. Other tags are removed when you save.
                        Placeholders, replaced for each member:
                        @foreach (\App\Models\CardSetting::PLACEHOLDERS as $name => $meaning)
                            <code title="{{ $meaning }}">{{ '{' . $name . '}' }}</code>
                        @endforeach
                    </div>
                    @error('letter_template') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Save card settings</button>
        </div>
    </form>
@endsection
