{{-- Contact Us form. Shown under the Contact Us page text. --}}
<section class="contact-wrap">
    <div class="contact-form-card card-public form-public">
        <h2>Send us a message</h2>
        @if (session('success'))<div class="notice notice-success">{{ session('success') }}</div>@endif
        <form method="POST" action="{{ route('contact.store') }}" novalidate>
            @csrf
            <div class="hp-field" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="form-grid">
                <div class="form-group"><label class="form-label" for="c-name">Name <span class="req">*</span></label>
                    <input class="form-input" id="c-name" name="name" maxlength="150" value="{{ old('name') }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror</div>
                <div class="form-group"><label class="form-label" for="c-mobile">Mobile <span class="req">*</span></label>
                    <input class="form-input" id="c-mobile" name="mobile" maxlength="20" value="{{ old('mobile') }}" required>
                    @error('mobile') <div class="form-error">{{ $message }}</div> @enderror</div>
                <div class="form-group"><label class="form-label" for="c-email">Email</label>
                    <input class="form-input" id="c-email" type="email" name="email" maxlength="190" value="{{ old('email') }}">
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror</div>
                <div class="form-group"><label class="form-label" for="c-subject">Subject</label>
                    <input class="form-input" id="c-subject" name="subject" maxlength="200" value="{{ old('subject') }}">
                    @error('subject') <div class="form-error">{{ $message }}</div> @enderror</div>
            </div>
            <div class="form-group"><label class="form-label" for="c-message">Message <span class="req">*</span></label>
                <textarea class="form-input" id="c-message" name="message" rows="5" maxlength="2000" required>{{ old('message') }}</textarea>
                @error('message') <div class="form-error">{{ $message }}</div> @enderror</div>
            <button type="submit" class="btn btn-block">Send message</button>
        </form>
    </div>
</section>
