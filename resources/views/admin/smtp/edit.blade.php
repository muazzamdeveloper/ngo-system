@extends('layouts.admin')
@section('title', 'SMTP server configuration')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">SMTP configuration</h1>
            <p class="page-subtitle">Mail server used for official notifications and receipts.</p>
        </div>
    </div>
    <div class="split-grid">
        <form method="POST" action="{{ route('smtp.update') }}" class="card form-card">
            @csrf @method('PUT')
            <h2 class="section-title">Mail server details</h2>
            <div class="form-grid">
                <div class="form-group"><label class="form-label" for="host">SMTP host <span class="req">*</span></label>
                    <input class="form-input" id="host" name="host" value="{{ old('host', $host) }}" placeholder="smtp.example.com" required>
                    @error('host') <div class="form-error">{{ $message }}</div> @enderror</div>
                <div class="form-group"><label class="form-label" for="port">Port <span class="req">*</span></label>
                    <input class="form-input" id="port" type="number" name="port" value="{{ old('port', $port) }}" min="1" max="65535" required>
                    @error('port') <div class="form-error">{{ $message }}</div> @enderror</div>
                <div class="form-group"><label class="form-label" for="username">Username</label>
                    <input class="form-input" id="username" name="username" value="{{ old('username', $username) }}" autocomplete="off"></div>
                <div class="form-group"><label class="form-label" for="password">Password</label>
                    <input class="form-input" id="password" type="password" name="password" autocomplete="new-password" placeholder="{{ $hasPassword ? 'Saved — leave blank to keep' : 'Enter password' }}">
                    <div class="form-hint">Stored encrypted. Leave blank to keep the saved password.</div></div>
                <div class="form-group"><label class="form-label" for="from_address">From email <span class="req">*</span></label>
                    <input class="form-input" id="from_address" type="email" name="from_address" value="{{ old('from_address', $fromAddress) }}" required>
                    @error('from_address') <div class="form-error">{{ $message }}</div> @enderror</div>
                <div class="form-group"><label class="form-label" for="from_name">From name</label>
                    <input class="form-input" id="from_name" name="from_name" value="{{ old('from_name', $fromName) }}"></div>
                <div class="form-group span-2"><label class="form-label" for="encryption">Encryption <span class="req">*</span></label>
                    <select class="form-input" id="encryption" name="encryption">
                        <option value="tls" @selected($encryption === 'tls')>TLS (recommended, port 587)</option>
                        <option value="ssl" @selected($encryption === 'ssl')>SSL (port 465)</option>
                        <option value="none" @selected($encryption === 'none')>None</option>
                    </select></div>
            </div>
            <div class="form-actions"><button type="submit" class="btn">Save SMTP settings</button></div>
        </form>

        <div>
            <form method="POST" action="{{ route('smtp.test') }}" class="card form-card">
                @csrf
                <h2 class="section-title">Connection test</h2>
                <p class="small muted">Sends one test email using the saved settings.</p>
                <div class="form-group"><label class="form-label" for="recipient">Recipient email</label>
                    <input class="form-input" id="recipient" type="email" name="recipient" required></div>
                <button type="submit" class="btn btn-block">Send test email</button>
            </form>
            <div class="notice notice-info">If you use Gmail, create an App Password and enable two-step verification first.</div>
        </div>
    </div>
@endsection
