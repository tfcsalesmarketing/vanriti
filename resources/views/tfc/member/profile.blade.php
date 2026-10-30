@extends('tfc.member.layouts.portal')

@section('title', 'Member Profile & Security')

@section('content')
<div class="container-fluid px-0">

    <div class="row g-4">
        {{-- Profile Details Card --}}
        <div class="col-lg-7">
            <div class="member-card p-4 p-md-5">
                <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">
                    <i class="bi bi-person-circle text-primary me-1"></i> Personal Profile
                </h5>

                {{-- Non-editable Membership Identifiers --}}
                <div class="p-3 mb-4 rounded-3 bg-light border">
                    <div class="row g-3 small">
                        <div class="col-sm-6">
                            <span class="text-muted d-block">TFC Member ID:</span>
                            <span class="font-monospace fw-bold text-primary fs-6">{{ $member->tfc_member_id }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Enrollment Date:</span>
                            <strong class="text-dark">{{ $member->joined_at ? $member->joined_at->format('d M Y') : '—' }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Direct Sponsor:</span>
                            <strong class="text-dark">{{ $member->sponsor ? $member->sponsor->full_name . ' (' . $member->sponsor->tfc_member_id . ')' : 'Direct TFC' }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Placement Parent &amp; Leg:</span>
                            <strong class="text-dark">{{ $member->placementParent ? $member->placementParent->full_name : 'Root' }} ({{ ucfirst($member->placement_position ?? 'root') }})</strong>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('tfc.preview.member.profile.update') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $member->full_name) }}" required>
                        @error('full_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Registered Email</label>
                            <input type="email" class="form-control bg-light" value="{{ $member->email }}" disabled readonly>
                            <div class="form-text" style="font-size: 0.72rem;">Email cannot be edited directly for security.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Registered Mobile</label>
                            <input type="text" class="form-control bg-light" value="{{ $member->mobile }}" disabled readonly>
                            <div class="form-text" style="font-size: 0.72rem;">Mobile is bound to your account.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Address Line</label>
                        <input type="text" name="address_line" class="form-control @error('address_line') is-invalid @enderror" value="{{ old('address_line', $member->address_line) }}" placeholder="House/Flat, Street name">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $member->city) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">State</label>
                            <input type="text" name="state" class="form-control" value="{{ old('state', $member->state) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Pincode</label>
                            <input type="text" name="pincode" class="form-control @error('pincode') is-invalid @enderror" maxlength="6" value="{{ old('pincode', $member->pincode) }}">
                            @error('pincode')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Update Profile Information
                    </button>
                </form>
            </div>
        </div>

        {{-- Change Password Card --}}
        <div class="col-lg-5">
            <div class="member-card p-4 p-md-5">
                <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">
                    <i class="bi bi-shield-lock text-success me-1"></i> Change Password
                </h5>

                <form method="POST" action="{{ route('tfc.preview.member.profile.password') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Current Password <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-outline-dark w-100 fw-semibold">
                        <i class="bi bi-key me-1"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
