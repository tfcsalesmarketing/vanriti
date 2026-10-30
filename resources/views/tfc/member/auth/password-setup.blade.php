@extends('tfc.layouts.app')

@section('title', 'Set Up Your Member Password | TFC Sales & Marketing')

@section('content')
<div class="py-5 bg-light" style="min-height: 80vh;">
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5 col-xl-4">

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header text-center bg-white py-4 px-4 border-bottom">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-3 p-2.5 mb-2" style="background: #0A1D37; color: #25D366;">
                            <i class="bi bi-shield-lock-fill fs-3"></i>
                        </div>
                        <h4 class="fw-bold mb-1 brand-font text-dark">Set Up Portal Password</h4>
                        <div class="text-muted small">Secure Expiring WhatsApp Access Link</div>
                    </div>

                    <div class="card-body p-4 p-md-4">
                        <div class="p-3 mb-4 rounded-3 bg-light border">
                            <div class="text-muted small">Member: <strong>{{ $member->full_name }}</strong></div>
                            <div class="text-muted small">Member ID: <span class="font-monospace fw-bold text-dark">{{ $member->tfc_member_id }}</span></div>
                        </div>

                        <form method="POST" action="{{ request()->fullUrl() }}">
                            @csrf
                            <input type="hidden" name="member_id" value="{{ $member->tfc_member_id }}">

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">New Password <span class="text-danger">*</span></label>
                                <input type="password" 
                                       name="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       placeholder="Minimum 8 characters" 
                                       required 
                                       autofocus>
                                @error('password')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" 
                                       name="password_confirmation" 
                                       class="form-control" 
                                       placeholder="Re-enter password" 
                                       required>
                            </div>

                            <button type="submit" class="btn btn-tfc-primary w-100 py-2.5 fw-bold shadow-sm">
                                <i class="bi bi-check-circle-fill me-1"></i> Save Password &amp; Enter Portal
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
