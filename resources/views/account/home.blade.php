@extends('layouts.member')

@section('title', 'My profile')

@section('content')
    <div class="page-header">
        <h1 class="page-title">My profile</h1>

        @if ($member)
            <div class="actions-row">
                @can('portal.edit_profile')
                    <a href="{{ route('account.profile.edit') }}" class="btn">Edit my details</a>
                @endcan
                <a href="{{ route('password.edit') }}" class="btn btn-light">Change password</a>
            </div>
        @endif
    </div>

    @if ($member)
        <div class="card">
            <div class="profile-layout">
                <div>
                    @if ($member->photo_path)
                        <img class="member-photo" src="{{ route('account.photo') }}" alt="Your photo">
                    @else
                        <div class="member-photo member-photo-empty">No photo</div>
                    @endif
                </div>

                <dl class="details">
                    <dt>Name</dt>
                    <dd>{{ $member->full_name }} <span class="badge badge-{{ $member->status }}">{{ ucfirst($member->status) }}</span></dd>

                    <dt>Membership number</dt>
                    <dd>{{ $member->membership_number ?? 'Not assigned yet' }}</dd>

                    <dt>Father's name</dt>
                    <dd>{{ $member->father_name }}</dd>

                    <dt>Date of birth</dt>
                    <dd>{{ $member->date_of_birth->format('d M Y') }}</dd>

                    <dt>Gender</dt>
                    <dd>{{ ucfirst($member->gender) }}</dd>

                    <dt>Mobile</dt>
                    <dd>{{ $member->mobile }}</dd>

                    <dt>Email</dt>
                    <dd>{{ $member->email }}</dd>

                    <dt>Address</dt>
                    <dd>{{ $member->address }}, {{ $member->city }}, {{ $member->state }} - {{ $member->pincode }}</dd>

                    <dt>Occupation</dt>
                    <dd>{{ $member->occupation ?? '-' }}</dd>

                    <dt>Aadhaar</dt>
                    <dd>{{ $member->maskedAadhaar() }}</dd>
                </dl>
            </div>
        </div>

        <div class="card action-card">
            <h2>My documents</h2>
            @cannot('portal.download_documents')
                <p class="text-muted">Document downloads are not available for your account. Please contact the organization.</p>
            @elseif ($member->isApproved())
                <div class="actions-row">
                    <a href="{{ route('account.document', 'id-card') }}" class="btn">Download ID card</a>
                    <a href="{{ route('account.document', 'joining-letter') }}" class="btn">Download joining letter</a>
                </div>
            @else
                <p class="text-muted">Your ID card and joining letter will be available here once your membership is approved.</p>
            @endif
        </div>
    @else
        <div class="card">
            <p>Your account is not linked to a membership record.</p>
            <p class="text-muted"><a href="{{ route('password.edit') }}">Change password</a></p>
        </div>
    @endif
@endsection
