@extends('admin.layouts.app')

@section('title', 'TFC WhatsApp API & Widget Settings')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.tfc.dashboard') }}" class="text-decoration-none">TFC Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.tfc.whatsapp.inbox') }}" class="text-decoration-none">WhatsApp</a></li>
            <li class="breadcrumb-item active">API &amp; Profile Settings</li>
        </ol>
    </nav>
    <h3 class="fw-bold mb-0 text-dark">
        <i class="bi bi-gear-wide-connected text-success me-2"></i>TFC WhatsApp Cloud API &amp; Widget Configuration
    </h3>
    <p class="text-muted small">Configure official Meta WhatsApp Cloud API credentials, webhook verification, and the public website floating WhatsApp chat widget.</p>
</div>

<div class="row g-4">
    {{-- ============================================================== --}}
    {{-- LEFT COLUMN: Settings Form                                     --}}
    {{-- ============================================================== --}}
    <div class="col-12 col-lg-8">
        <form method="POST" action="{{ route('admin.tfc.whatsapp.profile.update') }}">
            @csrf

            {{-- Cloud API Credentials --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-shield-lock-fill text-primary me-2"></i>Meta Cloud API Credentials
                    </h5>
                    @if ($isConfigured)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i>Configured &amp; Active
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Action Required
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-4">
                    Leave blank to inherit primary VANRITI credentials, or enter dedicated credentials if TFC Sales &amp; Marketing operates its own Meta Business Account / dedicated phone number.
                </p>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-dark">TFC Phone Number ID</label>
                        <input type="text" name="whatsapp_phone_number_id" class="form-control font-monospace"
                               placeholder="e.g., 104928374928172" value="{{ old('whatsapp_phone_number_id', $settings['phone_number_id']) }}">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-dark">WhatsApp Business Account (WABA) ID</label>
                        <input type="text" name="whatsapp_waba_id" class="form-control font-monospace"
                               placeholder="e.g., 294810293847291" value="{{ old('whatsapp_waba_id', $settings['waba_id']) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">
                            Permanent System User Access Token
                            @if ($settings['has_access_token'])
                                <span class="badge bg-success small ms-2"><i class="bi bi-check"></i> Stored Encrypted</span>
                            @endif
                        </label>
                        <input type="password" name="whatsapp_access_token" class="form-control font-monospace"
                               placeholder="{{ $settings['has_access_token'] ? '•••••••••••••••••••••••• (Leave blank to keep existing)' : 'EAABw...' }}">
                    </div>
                </div>
            </div>

            {{-- Public Floating WhatsApp Widget Settings --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="bi bi-chat-dots-fill text-success me-2"></i>Public Floating WhatsApp Widget
                </h5>
                <p class="text-muted small mb-4">
                    Controls the floating WhatsApp chat button displayed on the public TFC website (<code>tfcsalesmarketing.com</code>) allowing prospective direct sellers and consumers to initiate instant conversations.
                </p>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="whatsapp_widget_enabled" value="1" id="widgetSwitch"
                           {{ $settings['widget_enabled'] ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold text-dark" for="widgetSwitch">
                        Enable Floating WhatsApp Button on TFC Public Website
                    </label>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-dark">Business WhatsApp Number</label>
                        <input type="text" name="whatsapp_phone_number" class="form-control"
                               placeholder="+91 98765 43210" value="{{ old('whatsapp_phone_number', $settings['whatsapp_phone_number']) }}">
                        <div class="form-text small">Number clicked to open WhatsApp chat.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-dark">Auto Chatbot Responses</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="whatsapp_bot_enabled" value="1" id="botSwitch"
                                   {{ $settings['bot_enabled'] ? 'checked' : '' }}>
                            <label class="form-check-label small text-dark" for="botSwitch">
                                Enable Chatbot Auto-Replies on Inbound Messages
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Floating Widget Tooltip Greeting</label>
                        <input type="text" name="whatsapp_widget_greeting" class="form-control"
                               placeholder="Have questions about our products or direct selling? Chat with us!"
                               value="{{ old('whatsapp_widget_greeting', $settings['widget_greeting']) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Pre-filled Message</label>
                        <textarea name="whatsapp_widget_default_msg" class="form-control" rows="2"
                                  placeholder="Hello TFC Team, I am interested in learning more...">{{ old('whatsapp_widget_default_msg', $settings['widget_default_msg']) }}</textarea>
                        <div class="form-text small">Text automatically typed into the customer's WhatsApp when they click the widget.</div>
                    </div>
                </div>
            </div>

            {{-- Approved Meta WhatsApp Templates --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-file-earmark-check-fill text-success me-2"></i>Approved Meta WhatsApp Templates
                    </h5>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">2 Active Templates</span>
                </div>
                <p class="text-muted small mb-3">
                    Edit your 2 pre-approved Meta template names used for member registration onboarding and OTP mobile verification.
                </p>

                <div class="row g-3">
                    {{-- 1. Welcome Onboarding Template --}}
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 p-3 bg-light h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-bold text-dark mb-0">1. Welcome Onboarding</label>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" style="font-size: 0.7rem;">MARKETING</span>
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white"><i class="bi bi-chat-heart text-muted"></i></span>
                                <input type="text" name="whatsapp_welcome_template" class="form-control font-monospace fw-semibold"
                                       placeholder="tfc_welcome" value="{{ old('whatsapp_welcome_template', $settings['welcome_template']) }}">
                            </div>
                            <div class="text-muted small" style="font-size: 0.76rem;">
                                <strong>Dispatched:</strong> On member registration<br>
                                <strong>Approved Name:</strong> <code>tfc_welcome</code>
                            </div>
                        </div>
                    </div>

                    {{-- 2. OTP Verification Template --}}
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 p-3 bg-light h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-bold text-dark mb-0">2. OTP Verification</label>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle font-monospace" style="font-size: 0.7rem;">AUTHENTICATION</span>
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white"><i class="bi bi-shield-lock text-muted"></i></span>
                                <input type="text" name="whatsapp_otp_template" class="form-control font-monospace fw-semibold"
                                       placeholder="tfc_otp_verification" value="{{ old('whatsapp_otp_template', $settings['otp_template']) }}">
                            </div>
                            <div class="text-muted small" style="font-size: 0.76rem;">
                                <strong>Dispatched:</strong> On mobile verification &amp; password setup<br>
                                <strong>Approved Name:</strong> <code>tfc_otp_verification</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4 text-end">
                <button type="submit" class="btn btn-success px-4 py-2 shadow-sm fw-semibold">
                    <i class="bi bi-save me-1"></i> Save Configuration
                </button>
            </div>
        </form>
    </div>

    {{-- ============================================================== --}}
    {{-- RIGHT COLUMN: Meta Diagnostics & Test Send                     --}}
    {{-- ============================================================== --}}
    <div class="col-12 col-lg-4">
        {{-- Live Connection Health --}}
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-heart-pulse-fill text-danger me-2"></i>Account Health (Meta API)</h6>

            @if ($accountHealth['success'] && !empty($accountHealth['data']))
                @php $data = $accountHealth['data']; @endphp
                <div class="small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Display Phone:</span>
                        <strong class="text-dark">{{ $data['display_phone_number'] ?? 'N/A' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Verified Name:</span>
                        <strong class="text-dark">{{ $data['verified_name'] ?? 'N/A' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Quality Rating:</span>
                        <span class="badge bg-success-subtle text-success">{{ $data['quality_rating'] ?? 'GREEN' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Messaging Limit:</span>
                        <strong class="text-dark">{{ $data['messaging_limit_tier'] ?? 'TIER_1K' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Status:</span>
                        <span class="badge bg-success">{{ $data['status'] ?? 'CONNECTED' }}</span>
                    </div>
                </div>
            @else
                <div class="p-3 bg-light rounded-3 text-center small text-muted">
                    <i class="bi bi-info-circle display-6 opacity-50 d-block mb-2"></i>
                    @if (!$isConfigured)
                        Enter Cloud API credentials on the left to verify connection.
                    @else
                        {{ $accountHealth['message'] ?? 'Could not fetch live Meta health. Verify access token.' }}
                    @endif
                </div>
            @endif
        </div>

        {{-- Meta Webhook Setup Card --}}
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-link-45deg text-primary me-2"></i>Meta Webhook Callback</h6>
            <p class="text-muted small mb-3">Copy these into your Meta App Dashboard under WhatsApp &gt; Configuration:</p>

            <div class="mb-3">
                <label class="form-label small text-muted mb-1">Callback URL</label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control font-monospace" value="{{ $webhookUrl }}" readonly id="inputWebhookUrl">
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('inputWebhookUrl').value); alert('Copied!');">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label small text-muted mb-1">Verify Token</label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control font-monospace" value="{{ $verifyToken }}" readonly id="inputVerifyToken">
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('inputVerifyToken').value); alert('Copied!');">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Instant Test Send Card --}}
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-send text-success me-2"></i>Send Test Message</h6>
            <p class="text-muted small mb-3">Send a real-time test WhatsApp message to verify Cloud API dispatch.</p>

            <form method="POST" action="{{ route('admin.tfc.whatsapp.profile.test-send') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Destination Phone</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">+</span>
                        <input type="text" name="test_phone" class="form-control font-monospace" placeholder="919876543210" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-outline-success w-100">
                    <i class="bi bi-send-fill me-1"></i>Send Test Message
                </button>
            </form>
        </div>

        {{-- Test Member Welcome Notification Card --}}
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-4 border-success">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-whatsapp text-success me-2"></i>Test Welcome Credentials</h6>
            <p class="text-muted small mb-3">Test send the real Welcome Message with sample User ID, password, and portal login link.</p>

            <form method="POST" action="{{ route('admin.tfc.whatsapp.profile.test-welcome') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Destination Phone</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">+</span>
                        <input type="text" name="test_phone" class="form-control font-monospace" placeholder="919876543210" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-success w-100 shadow-sm">
                    <i class="bi bi-person-check-fill me-1"></i>Send Welcome Template Test
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
