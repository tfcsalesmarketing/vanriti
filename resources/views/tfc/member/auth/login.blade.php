@extends('tfc.layouts.app')

@section('title', 'TFC Member Portal Login')
@section('meta_description', 'Sign in to your independent TFC Sales & Marketing Direct Selling Member Dashboard to view your genealogy tree, team counts and profile.')

@section('content')
<div class="py-5 bg-light" style="min-height: 80vh;">
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5 col-xl-4">

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header text-center bg-white py-4 px-4 border-bottom">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-3 p-2.5 mb-2" style="background: #0A1D37; color: #D49B24;">
                            <i class="bi bi-diagram-3-fill fs-3"></i>
                        </div>
                        <h4 class="fw-bold mb-1 brand-font text-dark">Member Portal Login</h4>
                        <div class="text-muted small">TFC Sales &amp; Marketing Direct Selling</div>
                    </div>

                    <div class="card-body p-4 p-md-4">
                        {{-- Flash Alerts --}}
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show d-flex align-items-start gap-2 small mb-3 py-2.5" role="alert">
                                <i class="bi bi-check-circle-fill text-success fs-6 mt-0.5"></i>
                                <div class="flex-grow-1">{{ session('success') }}</div>
                                <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('warning'))
                            <div class="alert alert-warning alert-dismissible fade show d-flex align-items-start gap-2 small mb-3 py-2.5" role="alert">
                                <i class="bi bi-exclamation-triangle-fill text-warning fs-6 mt-0.5"></i>
                                <div class="flex-grow-1">{{ session('warning') }}</div>
                                <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('info'))
                            <div class="alert alert-info alert-dismissible fade show d-flex align-items-start gap-2 small mb-3 py-2.5" role="alert">
                                <i class="bi bi-info-circle-fill text-info fs-6 mt-0.5"></i>
                                <div class="flex-grow-1">{{ session('info') }}</div>
                                <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 small mb-3 py-2.5" role="alert">
                                <i class="bi bi-exclamation-octagon-fill text-danger fs-6 mt-0.5"></i>
                                <div class="flex-grow-1">{{ session('error') }}</div>
                                <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        {{-- Live Member Verification Card (Appears dynamically) --}}
                        <div id="verifiedMemberCard" class="p-3 mb-3 rounded-3 bg-light border d-none">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" id="loginAvatar">
                                    M
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-dark small text-truncate" id="loginMemberName">—</div>
                                    <div class="text-success small" style="font-size: 0.75rem;">
                                        <i class="bi bi-check-circle-fill"></i> Direct Seller Verified (<span id="loginMemberId"></span>)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ function_exists('tfc_route') ? tfc_route('member.login.submit') : route('tfc.preview.member.login.submit') }}" id="memberLoginForm">
                            @csrf

                            {{-- Login Identifier (Member ID, Mobile, or Email) with Live Check --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Member ID, Email, or Mobile</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                                    <input type="text" 
                                           name="login" 
                                           id="loginIdentifierInput"
                                           class="form-control @error('login') is-invalid @enderror" 
                                           placeholder="e.g. TFC10001, phone, or email" 
                                           value="{{ old('login', session('prefill_login')) }}" 
                                           required 
                                           autofocus>
                                    <span class="input-group-text bg-white" id="loginCheckIcon">
                                        <i class="bi bi-search text-muted"></i>
                                    </span>
                                </div>
                                <div id="loginFeedback" class="small mt-1"></div>
                                @error('login')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Password --}}
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label fw-semibold small mb-0">Password</label>
                                </div>
                                <div class="input-group mt-1">
                                    <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                                    <input type="password" 
                                           name="password" 
                                           id="loginPasswordInput"
                                           class="form-control @error('password') is-invalid @enderror" 
                                           placeholder="••••••••" 
                                           required>
                                </div>
                                @error('password')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                                <label class="form-check-label small text-muted" for="remember">Keep me signed in</label>
                            </div>

                            <button type="submit" id="btnLoginSubmit" class="btn btn-tfc-primary w-100 py-2.5 fw-bold shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-1.5"></i> Sign In to Portal
                            </button>
                        </form>
                    </div>

                    <div class="card-footer bg-light py-3 text-center border-top">
                        <div class="small text-muted mb-2">New to TFC Sales &amp; Marketing?</div>
                        <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-outline-dark btn-sm w-100 fw-semibold">
                            <i class="bi bi-person-plus me-1"></i> Register as Direct Seller (Free)
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function () {
    const loginInput = document.getElementById('loginIdentifierInput');
    const loginCheckIcon = document.getElementById('loginCheckIcon');
    const loginFeedback = document.getElementById('loginFeedback');
    const memberCard = document.getElementById('verifiedMemberCard');
    const memberName = document.getElementById('loginMemberName');
    const memberId = document.getElementById('loginMemberId');
    const avatar = document.getElementById('loginAvatar');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let debounceTimer = null;

    loginInput.addEventListener('input', function () {
        const val = this.value.trim();
        clearTimeout(debounceTimer);

        if (val.length >= 4) {
            loginCheckIcon.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
            debounceTimer = setTimeout(checkLoginAccount, 400);
        } else {
            loginFeedback.innerHTML = '';
            memberCard.classList.add('d-none');
            loginCheckIcon.innerHTML = '<i class="bi bi-search text-muted"></i>';
        }
    });

    loginInput.addEventListener('blur', function () {
        const val = this.value.trim();
        if (val.length >= 4) {
            checkLoginAccount();
        }
    });

    function checkLoginAccount() {
        const val = loginInput.value.trim();
        if (!val) return;

        fetch("{{ route('tfc.preview.member.ajax.check-login') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ login: val })
        })
        .then(res => res.json())
        .then(data => {
            if (data.valid) {
                loginCheckIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                loginFeedback.innerHTML = '';
                memberName.innerText = data.name;
                memberId.innerText = data.member_id;
                avatar.innerText = data.initial;
                memberCard.classList.remove('d-none');
            } else {
                loginCheckIcon.innerHTML = '<i class="bi bi-x-circle text-muted"></i>';
                loginFeedback.innerHTML = `<span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i> ${data.message}</span>`;
                memberCard.classList.add('d-none');
            }
        })
        .catch(() => {
            loginCheckIcon.innerHTML = '<i class="bi bi-person text-muted"></i>';
        });
    }

    // Auto-check if page loads with old input
    if (loginInput.value.trim().length >= 4) {
        checkLoginAccount();
    }
});
</script>
@endpush
