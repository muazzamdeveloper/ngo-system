{{-- Expects: $staff (User), $roles, $editing (bool) --}}
<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="name">Name *</label>
        <input class="form-input" type="text" id="name" name="name"
               value="{{ old('name', $staff->name) }}" maxlength="255" required>
        @error('name') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="email">Email *</label>
        <input class="form-input" type="email" id="email" name="email"
               value="{{ old('email', $staff->email) }}" maxlength="255" required autocomplete="off">
        @error('email') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="role_id">Role *</label>
        <select class="form-input" id="role_id" name="role_id" required>
            <option value="">Select a role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected((int) old('role_id', $staff->role_id) === $role->id)>{{ $role->label }}</option>
            @endforeach
        </select>
        <div class="form-hint">You can only give roles that do not exceed your own permissions.</div>
        @error('role_id') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group"></div>

    <div class="form-group">
        <label class="form-label" for="password">{{ $editing ? 'New password' : 'Temporary password *' }}</label>
        <input class="form-input" type="password" id="password" name="password"
               autocomplete="new-password" @required(! $editing)>
        <div class="form-hint">
            At least 10 characters, with letters and numbers.
            @if ($editing) Leave blank to keep the current password. @endif
            The user must choose a new password at the next login.
        </div>
        @error('password') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="password_confirmation">Confirm password</label>
        <input class="form-input" type="password" id="password_confirmation" name="password_confirmation"
               autocomplete="new-password" @required(! $editing)>
    </div>
</div>
