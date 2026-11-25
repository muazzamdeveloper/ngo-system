@extends('layouts.admin')

@section('title', 'Organization settings')

@section('content')
    <h1 class="page-title">Organization settings</h1>

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card form-card">
            <h2 class="section-title">Organization</h2>

            <div class="form-group">
                <label class="form-label" for="name">Organization name</label>
                <input class="form-input" type="text" id="name" name="name"
                       value="{{ old('name', $setting->name) }}" maxlength="100" required>
                @error('name')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="membership_prefix">Membership number prefix</label>
                <input class="form-input" type="text" id="membership_prefix" name="membership_prefix"
                       value="{{ old('membership_prefix', $setting->membership_prefix) }}"
                       maxlength="5" required style="text-transform: uppercase;">
                <div class="form-hint">
                    1 to 5 letters or digits. Membership numbers will look like
                    <strong>{{ old('membership_prefix', $setting->membership_prefix) }}-{{ date('Y') }}-0001</strong>.
                    Changing it affects only new numbers, never existing ones.
                </div>
                @error('membership_prefix')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card form-card action-card">
            <h2 class="section-title">Site branding</h2>
            <p class="form-hint">These appear in the admin panel, the login page, the member area and the public pages.</p>

            <div class="form-group">
                <label class="form-label" for="site_title">Site title</label>
                <input class="form-input" type="text" id="site_title" name="site_title"
                       value="{{ old('site_title', $setting->site_title) }}" maxlength="100">
                <div class="form-hint">Shown in the browser tab. Leave empty to use the organization name.</div>
                @error('site_title') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="tagline">Tagline</label>
                <input class="form-input" type="text" id="tagline" name="tagline"
                       value="{{ old('tagline', $setting->tagline) }}" maxlength="160">
                <div class="form-hint">A short line under the name on the login and public pages. Optional.</div>
                @error('tagline') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="logo">Logo</label>
                @if ($setting->logoUrl())
                    <img class="brand-logo-large" style="margin-left: 0;" src="{{ $setting->logoUrl() }}" alt="Current logo">
                @endif
                <input class="form-input" type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
                <div class="form-hint">PNG, JPG or WebP, up to 2 MB. A transparent PNG works best. Also used on ID cards and letters when no card logo is set.</div>
                @if ($setting->logoUrl())
                    <label class="form-check"><input type="checkbox" name="remove_logo" value="1"><span>Remove current logo</span></label>
                @endif
                @error('logo') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="favicon">Site icon (favicon)</label>
                @if ($setting->faviconUrl())
                    <img class="member-photo-small" style="width: 40px; height: 40px;" src="{{ $setting->faviconUrl() }}" alt="Current icon">
                @endif
                <input class="form-input" type="file" id="favicon" name="favicon" accept="image/png,image/jpeg,image/webp,.ico">
                <div class="form-hint">The small icon in the browser tab. A square PNG or ICO, up to 512 KB.</div>
                @if ($setting->faviconUrl())
                    <label class="form-check"><input type="checkbox" name="remove_favicon" value="1"><span>Remove current icon</span></label>
                @endif
                @error('favicon') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="primary_color">Main colour</label>
                <input type="color" id="primary_color" name="primary_color"
                       value="{{ old('primary_color', $setting->primaryColor()) }}" style="width: 4rem; height: 2.4rem; padding: 0;">
                <div class="form-hint">Used for buttons, the sidebar and headings. Dark colours work best because text on them is white.</div>
                <label class="form-check"><input type="checkbox" name="reset_color" value="1"><span>Use the default colour</span></label>
                @error('primary_color') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="card form-card action-card">
            <h2 class="section-title">Contact &amp; social (header and footer)</h2>
            <p class="form-hint">Shown in the header and footer of the public pages. Empty boxes are simply not shown.</p>

            <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-input" type="text" id="phone" name="phone" maxlength="30" placeholder="+91 98765 43210" value="{{ old('phone', $setting->phone) }}">
                @error('phone') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input class="form-input" type="email" id="email" name="email" maxlength="190" value="{{ old('email', $setting->email) }}">
                @error('email') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="whatsapp">WhatsApp number</label>
                <input class="form-input" type="text" id="whatsapp" name="whatsapp" maxlength="20" placeholder="+91 98765 43210" value="{{ old('whatsapp', $setting->whatsapp) }}">
                <div class="form-hint">Adds a WhatsApp button that opens a chat with this number.</div>
                @error('whatsapp') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="address">Head office address</label>
                <input class="form-input" type="text" id="address" name="address" maxlength="300" value="{{ old('address', $setting->address) }}">
                @error('address') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="address_secondary">Registered office address</label>
                <input class="form-input" type="text" id="address_secondary" name="address_secondary" maxlength="300" value="{{ old('address_secondary', $setting->address_secondary) }}">
                @error('address_secondary') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="working_hours">Working hours</label>
                <input class="form-input" type="text" id="working_hours" name="working_hours" maxlength="120" placeholder="Mon - Sat, 10:00 AM - 6:00 PM" value="{{ old('working_hours', $setting->working_hours) }}">
                @error('working_hours') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="facebook_url">Facebook link</label>
                <input class="form-input" type="url" id="facebook_url" name="facebook_url" maxlength="255" placeholder="https://facebook.com/yourpage" value="{{ old('facebook_url', $setting->facebook_url) }}">
                @error('facebook_url') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="instagram_url">Instagram link</label>
                <input class="form-input" type="url" id="instagram_url" name="instagram_url" maxlength="255" placeholder="https://instagram.com/yourpage" value="{{ old('instagram_url', $setting->instagram_url) }}">
                @error('instagram_url') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="youtube_url">YouTube link</label>
                <input class="form-input" type="url" id="youtube_url" name="youtube_url" maxlength="255" placeholder="https://youtube.com/@yourchannel" value="{{ old('youtube_url', $setting->youtube_url) }}">
                @error('youtube_url') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="ticker_label">News ticker label</label>
                <input class="form-input" type="text" id="ticker_label" name="ticker_label" maxlength="30" placeholder="Latest news" value="{{ old('ticker_label', $setting->ticker_label) }}">
                <div class="form-hint">The small tag before the sliding headlines. Headlines are managed under "News ticker".</div>
                @error('ticker_label') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-actions" style="margin-top: 1rem;">
            <button type="submit" class="btn">Save settings</button>
        </div>
    </form>
@endsection
