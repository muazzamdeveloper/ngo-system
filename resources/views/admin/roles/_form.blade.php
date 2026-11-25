{{-- Expects: $role, $groups (permissions grouped), $selected (ids) --}}
<div class="form-group">
    <label class="form-label" for="label">Role name *</label>
    <input class="form-input" type="text" id="label" name="label"
           value="{{ old('label', $role->label) }}" maxlength="60" required>
    @error('label') <div class="form-error">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    <label class="form-label" for="description">Description</label>
    <input class="form-input" type="text" id="description" name="description"
           value="{{ old('description', $role->description) }}" maxlength="255">
    @error('description') <div class="form-error">{{ $message }}</div> @enderror
</div>

<h2 class="section-title">Permissions</h2>
@if ($role->isMemberRole())
    <p class="form-hint">
        These control what a member can do in their own area. Members never get access to the admin panel.
    </p>
@else
    <p class="form-hint">
        A role with any permission can enter the admin panel. Any "members" permission also allows viewing members.
    </p>
@endif

@php($chosen = collect(old('permissions', $selected))->map(fn ($id) => (int) $id)->all())

<div class="permission-groups">
    @foreach ($groups as $group => $permissions)
        <fieldset class="permission-group">
            <legend>{{ $group }}</legend>
            @foreach ($permissions as $permission)
                <label class="form-check">
                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                           @checked(in_array($permission->id, $chosen, true))>
                    <span>{{ $permission->label }}</span>
                </label>
            @endforeach
        </fieldset>
    @endforeach
</div>
@error('permissions.*') <div class="form-error">{{ $message }}</div> @enderror
