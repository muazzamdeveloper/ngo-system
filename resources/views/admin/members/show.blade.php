@extends('layouts.admin')

@section('title', $member->full_name)

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ $member->full_name }}
            <span class="badge badge-{{ $member->status }}">{{ ucfirst($member->status) }}</span>
        </h1>

        <div class="actions-row">
            <a href="{{ route('members.index') }}" class="btn btn-light">Back to list</a>
            @can('members.edit')
                <a href="{{ route('members.edit', $member) }}" class="btn">Edit</a>
            @endcan
        </div>
    </div>

    @if (session('new_password'))
        <div class="alert alert-success">
            Temporary password (shown only once, please note it down):
            <code class="password-box">{{ session('new_password') }}</code>
        </div>
    @endif

    @if ($errors->has('status'))
        <div class="alert alert-danger">{{ $errors->first('status') }}</div>
    @endif

    <div class="card">
        <div class="profile-layout">
            <div>
                @if ($member->photo_path)
                    <img class="member-photo" src="{{ route('members.photo', $member) }}" alt="Photo of {{ $member->full_name }}">
                @else
                    <div class="member-photo member-photo-empty">No photo</div>
                @endif
            </div>

            <dl class="details">
                <dt>Membership number</dt>
                <dd>{{ $member->membership_number ?? 'Not assigned yet' }}</dd>

                <dt>Father's name</dt>
                <dd>{{ $member->father_name }}</dd>

                <dt>Date of birth</dt>
                <dd>{{ $member->date_of_birth->format('d M Y') }}</dd>

                <dt>Gender</dt>
                <dd>{{ ucfirst($member->gender) }}</dd>

                <dt>Blood group</dt>
                <dd>{{ $member->blood_group ?? '-' }}</dd>

                <dt>Mobile</dt>
                <dd>{{ $member->mobile }}</dd>

                <dt>WhatsApp</dt>
                <dd>{{ $member->whatsapp ?? '-' }}</dd>

                <dt>Email</dt>
                <dd>{{ $member->email ?? '-' }}</dd>

                <dt>Address</dt>
                <dd>{{ $member->address }}, {{ $member->city }}, {{ $member->state }} - {{ $member->pincode }}</dd>

                <dt>District</dt>
                <dd>{{ $member->district ?? '-' }}</dd>

                <dt>Work area</dt>
                <dd>{{ $member->work_area ?? '-' }}</dd>

                <dt>Source</dt>
                <dd>{{ $member->source === 'online' ? 'Online application' : 'Added by staff' }}</dd>

                <dt>Occupation</dt>
                <dd>{{ $member->occupation ?? '-' }}</dd>

                <dt>Aadhaar</dt>
                <dd>
                    @if ($revealedAadhaar)
                        <strong>{{ trim(chunk_split($revealedAadhaar, 4, ' ')) }}</strong>
                        <span class="text-muted">(shown once; this view was logged)</span>
                    @else
                        {{ $member->maskedAadhaar() }}
                        @if ($member->aadhaar_last4)
                            @can('members.view_aadhaar')
                                <form method="POST" action="{{ route('members.aadhaar', $member) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-small btn-light">Show full number</button>
                                </form>
                            @endcan
                        @endif
                    @endif
                </dd>

                <dt>Login account</dt>
                <dd>
                    @if ($member->user)
                        {{ $member->user->email }}
                        ({{ $member->user->is_active ? 'active' : 'disabled' }})
                    @else
                        None
                    @endif
                </dd>

                <dt>Added</dt>
                <dd>
                    {{ $member->created_at->format('d M Y, H:i') }}
                    @if ($member->creator) by {{ $member->creator->name }} @endif
                </dd>

                <dt>Last status decision</dt>
                <dd>
                    @if ($member->reviewed_at)
                        {{ $member->reviewed_at->format('d M Y, H:i') }}
                        @if ($member->reviewer) by {{ $member->reviewer->name }} @endif
                        @if ($member->status_note)
                            <br><span class="text-muted">Note: {{ $member->status_note }}</span>
                        @endif
                    @else
                        -
                    @endif
                </dd>
            </dl>
        </div>
    </div>

    <div class="card action-card">
        <h2>Documents</h2>
        <ul class="doc-list">
            @foreach (\App\Models\Member::DOCUMENT_LABELS as $field => $label)
                @php($column = \App\Models\Member::DOCUMENT_COLUMNS[$field])
                @php($isAadhaar = str_starts_with($field, 'aadhaar_'))
                <li>
                    <span>{{ $label }}</span>
                    @if (! $member->{$column})
                        <span class="text-muted">Not uploaded</span>
                    @elseif ($isAadhaar && ! auth()->user()->can('members.view_aadhaar'))
                        <span class="text-muted">Uploaded (restricted)</span>
                    @else
                        <a href="{{ route('members.document', [$member, str_replace('_', '-', $field)]) }}" target="_blank" rel="noopener">View</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    @if ($member->isApproved())
        <div class="card action-card">
            <h2>ID card and joining letter</h2>
            <div class="actions-row">
                @if (\App\Http\Controllers\Admin\MemberPdfController::canOpenIdCard(request()))
                    <a href="{{ route('members.pdf', [$member, 'id-card']) }}" class="btn">Download ID card</a>
                @else
                    <span class="text-muted">ID card (restricted: it prints the full Aadhaar number)</span>
                @endif
                <a href="{{ route('members.pdf', [$member, 'joining-letter']) }}" class="btn">Download joining letter</a>
            </div>
        </div>

    @endif

    @can('members.create_account')
        @if ($member->user_id === null)
            <div class="card action-card">
                <h2>Login account</h2>

                @if ($errors->has('account'))
                    <div class="alert alert-danger">{{ $errors->first('account') }}</div>
                @endif

                @if ($member->status !== \App\Models\Member::STATUS_APPROVED)
                    <p class="text-muted">Only approved members can get a login account.</p>
                @elseif (blank($member->email))
                    <p class="text-muted">This member has no email address. Add one with Edit first.</p>
                @else
                    <p class="text-muted">Creates a login for <strong>{{ $member->email }}</strong>. The member must choose a new password at the first login.</p>
                    <form method="POST" action="{{ route('members.account', $member) }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="account_password">Temporary password</label>
                            <input class="form-input" type="text" id="account_password" name="password"
                                   autocomplete="off" placeholder="Leave blank to generate one automatically">
                            <div class="form-hint">At least 8 characters, with letters and numbers.</div>
                            @error('password') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <label class="form-check">
                            <input type="checkbox" name="send_email" value="1" checked>
                            <span>Email the login details to the member</span>
                        </label>
                        <button type="submit" class="btn">Create login</button>
                    </form>
                @endif
            </div>
        @endif
    @endcan

    @php($st = $member->status)

    @if ($errors->has('note'))
        <div class="alert alert-danger">{{ $errors->first('note') }}</div>
    @endif

    @if ($st === \App\Models\Member::STATUS_PENDING)
        @can('members.approve')
            <div class="card action-card">
                <h2>Decision</h2>
                <p class="text-muted">Approving gives the member a membership number. Rejecting needs a reason.</p>

                <form method="POST" action="{{ route('members.approve', $member) }}" class="filters">
                    @csrf
                    <input class="form-input" type="text" name="note" maxlength="500" placeholder="Note (optional)">
                    <button type="submit" class="btn" onclick="return confirm('Approve this member?');">Approve</button>
                </form>

                <form method="POST" action="{{ route('members.reject', $member) }}" class="filters">
                    @csrf
                    <input class="form-input" type="text" name="note" maxlength="500" placeholder="Reason for rejection (required)" required minlength="3">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this member?');">Reject</button>
                </form>
            </div>
        @endcan
    @elseif ($st === \App\Models\Member::STATUS_REJECTED)
        @can('members.approve')
            <div class="card action-card">
                <h2>Reopen application</h2>
                <p class="text-muted">Moves the member back to Pending so a new decision can be made.</p>
                <form method="POST" action="{{ route('members.reopen', $member) }}" class="filters">
                    @csrf
                    <input class="form-input" type="text" name="note" maxlength="500" placeholder="Note (optional)">
                    <button type="submit" class="btn">Reopen</button>
                </form>
            </div>
        @endcan
    @elseif ($st === \App\Models\Member::STATUS_APPROVED)
        @can('members.deactivate')
            <div class="card action-card">
                <h2>Deactivate member</h2>
                <p class="text-muted">The member keeps their number but is no longer valid, and their login (if any) is switched off.</p>
                <form method="POST" action="{{ route('members.deactivate', $member) }}" class="filters">
                    @csrf
                    <input class="form-input" type="text" name="note" maxlength="500" placeholder="Reason (optional)">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Deactivate this member?');">Deactivate</button>
                </form>
            </div>

            <div class="card action-card">
                <h2>Block member</h2>
                <p class="text-muted">For misconduct. The member keeps their number but documents and login are disabled.</p>
                <form method="POST" action="{{ route('members.block', $member) }}" class="filters">
                    @csrf
                    <input class="form-input" type="text" name="note" maxlength="500" placeholder="Reason for blocking (required)" required minlength="3">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Block this member?');">Block</button>
                </form>
            </div>
        @endcan
    @elseif ($st === \App\Models\Member::STATUS_DEACTIVATED)
        @can('members.deactivate')
            <div class="card action-card">
                <h2>Restore member</h2>
                <p class="text-muted">The member becomes Approved again and their login (if any) is switched back on.</p>
                <form method="POST" action="{{ route('members.restore', $member) }}" class="filters">
                    @csrf
                    <input class="form-input" type="text" name="note" maxlength="500" placeholder="Note (optional)">
                    <button type="submit" class="btn">Restore</button>
                </form>
            </div>
        @endcan
    @elseif ($st === \App\Models\Member::STATUS_BLOCKED)
        @can('members.deactivate')
            <div class="card action-card">
                <h2>Unblock member</h2>
                <p class="text-muted">The member becomes Approved again with the same number, and their login (if any) is switched back on.</p>
                <form method="POST" action="{{ route('members.unblock', $member) }}" class="filters">
                    @csrf
                    <input class="form-input" type="text" name="note" maxlength="500" placeholder="Note (optional)">
                    <button type="submit" class="btn">Unblock</button>
                </form>
            </div>
        @endcan
    @endif
@endsection
