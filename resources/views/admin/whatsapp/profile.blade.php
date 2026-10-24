@extends('admin.layouts.app')

@section('title', 'WhatsApp Business Profile & Health - Vanriti Admin')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 text-muted" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp.inbox') }}" class="text-decoration-none">WhatsApp CRM</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Business Profile & Health</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 38px; height: 38px; background: rgba(37, 211, 102, 0.15); color: #25D366;">
                    <i class="bi bi-whatsapp fs-5"></i>
                </span>
                WhatsApp Business Profile & Health
            </h1>
            <p class="text-muted small mb-0">Official Meta Cloud API profile, tier messaging limits, real-time quality score, and webhook diagnostics.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('admin.whatsapp.profile.sync') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2">
                    <i class="bi bi-arrow-repeat me-1"></i> Sync from Meta
                </button>
            </form>
            <a href="{{ route('admin.whatsapp.inbox') }}" class="btn btn-success btn-sm rounded-pill px-3 py-2" style="background-color: #25D366; border-color: #25D366;">
                <i class="bi bi-chat-dots-fill me-1"></i> Open Live Inbox
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5 text-success"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-info-circle-fill fs-5 text-info"></i>
            <div>{{ session('info') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error') || $apiError)
        <div class="alert alert-warning alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
            <div>
                <strong>Meta Cloud API Notice:</strong> {{ session('error') ?? $apiError }}
                <div class="small mt-1">Check your API tokens and Phone Number ID in <a href="{{ route('admin.settings.index') }}" class="alert-link">Admin Settings &rarr; WhatsApp</a>.</div>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left Column: Business Card & Health Diagnostics -->
        <div class="col-lg-4">
            <!-- Official WhatsApp Business Card Mockup -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="p-4 text-white text-center position-relative" style="background: linear-gradient(135deg, #075E54 0%, #128C7E 50%, #25D366 100%);">
                    <div class="position-absolute top-0 end-0 p-3 opacity-25">
                        <i class="bi bi-whatsapp" style="font-size: 5rem; line-height: 1;"></i>
                    </div>
                    
                    <div class="position-relative z-1">
                        <div class="d-inline-flex position-relative mb-2">
                            <div class="rounded-circle bg-white p-1 shadow">
                                @if (!empty($profileData['profile_picture_url']))
                                    <img src="{{ $profileData['profile_picture_url'] }}" alt="Profile" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-light text-dark fw-bold d-flex align-items-center justify-content-center fs-2" style="width: 80px; height: 80px; font-family: serif;">
                                        V
                                    </div>
                                @endif
                            </div>
                            <span class="position-absolute bottom-0 end-0 bg-white text-success rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 26px; height: 26px;" title="Official Business WhatsApp">
                                <i class="bi bi-patch-check-fill fs-6"></i>
                            </span>
                        </div>

                        <h4 class="fw-bold mb-0 text-white d-flex align-items-center justify-content-center gap-1">
                            {{ $healthData['verified_name'] ?? 'Vanriti' }}
                        </h4>
                        <div class="small opacity-75 mt-1">
                            {{ $healthData['display_phone_number'] ?? ($phoneNumberId ? 'ID: ' . $phoneNumberId : '+91 Connected Line') }}
                        </div>

                        <div class="mt-2">
                            <span class="badge rounded-pill bg-white text-dark px-3 py-1 fw-semibold" style="font-size: 0.75rem;">
                                {{ $verticals[$profileData['vertical'] ?? ''] ?? ($profileData['vertical'] ?? 'Ayurvedic Wellness & Retail') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="text-uppercase text-muted fw-bold" style="font-size: 0.68rem; letter-spacing: 0.05em;">About / Status</label>
                        <p class="mb-0 text-dark small fw-medium">
                            {{ $profileData['about'] ?? 'Pure, natural & Ayurvedic wellness crafted with love by Vanriti. 🌿' }}
                        </p>
                    </div>

                    @if(!empty($profileData['description']))
                    <div class="mb-3">
                        <label class="text-uppercase text-muted fw-bold" style="font-size: 0.68rem; letter-spacing: 0.05em;">Description</label>
                        <p class="mb-0 text-muted small" style="line-height: 1.5;">
                            {{ $profileData['description'] }}
                        </p>
                    </div>
                    @endif

                    <hr class="my-3 opacity-10">

                    <div class="d-flex flex-column gap-2 small">
                        <div class="d-flex align-items-center gap-2 text-muted">
                            <i class="bi bi-envelope text-success"></i>
                            <span class="text-truncate text-dark">{{ $profileData['email'] ?? 'support@vanriti.com' }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted">
                            <i class="bi bi-globe text-success"></i>
                            <a href="{{ (!empty($profileData['websites'][0])) ? $profileData['websites'][0] : url('/') }}" target="_blank" class="text-decoration-none text-dark text-truncate">
                                {{ (!empty($profileData['websites'][0])) ? $profileData['websites'][0] : url('/') }}
                            </a>
                        </div>
                        <div class="d-flex align-items-start gap-2 text-muted">
                            <i class="bi bi-geo-alt text-success mt-1"></i>
                            <span class="text-dark">{{ $profileData['address'] ?? 'Jaipur, Rajasthan, India' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Meta Account Health & Quality Score -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                    <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-success"></i> Meta Health & Tier
                    </h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <!-- Quality Rating -->
                    @php
                        $quality = strtoupper($healthData['quality_rating'] ?? 'GREEN');
                        $qualityBadge = match($quality) {
                            'GREEN' => ['bg' => 'bg-success', 'text' => 'High Quality', 'desc' => 'Your phone number has a stellar reputation. Zero restrictions.'],
                            'YELLOW' => ['bg' => 'bg-warning text-dark', 'text' => 'Medium Quality', 'desc' => 'Some customers reported messages. Monitor campaign frequency.'],
                            'RED' => ['bg' => 'bg-danger', 'text' => 'Low Quality (Risk)', 'desc' => 'High block/spam rate. Reduce outbound broadcasts immediately.'],
                            default => ['bg' => 'bg-secondary', 'text' => 'Active / Standard', 'desc' => 'Operating normally under standard Meta Cloud API tier.']
                        };

                        $tier = strtoupper($healthData['messaging_limit_tier'] ?? 'TIER_10K');
                        $tierLabel = match($tier) {
                            'TIER_50' => '50 Customers / 24h (Sandbox/Unverified)',
                            'TIER_250' => '250 Customers / 24h',
                            'TIER_1K' => '1,000 Customers / 24h',
                            'TIER_10K' => '10,000 Customers / 24h',
                            'TIER_100K' => '100,000 Customers / 24h',
                            'TIER_UNLIMITED' => 'Unlimited Outbound Messages',
                            default => $tier
                        };
                    @endphp

                    <div class="p-3 rounded-3 bg-light mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small fw-semibold text-muted">Quality Rating</span>
                            <span class="badge {{ $qualityBadge['bg'] }} rounded-pill px-3 py-1">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> {{ $qualityBadge['text'] }}
                            </span>
                        </div>
                        <p class="small text-muted mb-0 mt-1">{{ $qualityBadge['desc'] }}</p>
                    </div>

                    <div class="p-3 rounded-3 bg-light mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small fw-semibold text-muted">Messaging Limit</span>
                            <span class="badge bg-primary rounded-pill px-2.5 py-1" style="font-size: 0.75rem;">{{ $tier }}</span>
                        </div>
                        <div class="small fw-bold text-dark mt-1">{{ $tierLabel }}</div>
                        <p class="small text-muted mb-0 mt-1">Applies to business-initiated conversations outside the 24h window.</p>
                    </div>

                    <div class="p-3 rounded-3 bg-light">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="small fw-semibold text-muted">Verification Status</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                <i class="bi bi-check-circle me-1"></i> {{ $healthData['code_verification_status'] ?? 'VERIFIED' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Technical Diagnostics -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                    <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-cpu text-secondary"></i> Cloud API Setup
                    </h5>
                </div>
                <div class="card-body px-4 pb-4 small">
                    <div class="mb-3">
                        <label class="text-muted fw-semibold mb-1">Webhook Callback URL</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" value="{{ $webhookUrl }}" id="webhookUrlInput" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('webhookUrlInput').value); alert('Webhook URL copied!');">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted fw-semibold mb-1">Verify Token</label>
                        <div class="input-group input-group-sm">
                            <input type="password" class="form-control font-monospace" value="{{ $verifyToken ?: 'vanriti_whatsapp_secret_token' }}" id="verifyTokenInput" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="const f=document.getElementById('verifyTokenInput'); f.type = f.type==='password'?'text':'password';">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row g-2 text-muted">
                        <div class="col-6">
                            <span class="d-block fw-semibold text-dark">Phone Number ID</span>
                            <span class="font-monospace text-truncate d-block">{{ $phoneNumberId ?: 'Not set' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="d-block fw-semibold text-dark">WABA ID</span>
                            <span class="font-monospace text-truncate d-block">{{ $wabaId ?: 'Not set' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Edit Profile & Sync With Meta -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-1">Edit Business Profile on Meta</h5>
                        <p class="text-muted small mb-0">Changes saved here are submitted directly to the Meta Graph API and update your live WhatsApp contact card.</p>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5">
                        <i class="bi bi-cloud-check me-1"></i> Live Meta Sync
                    </span>
                </div>

                <div class="card-body p-4 pt-2">
                    <form action="{{ route('admin.whatsapp.profile.update') }}" method="POST">
                        @csrf

                        <!-- About / Status -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="about" class="form-label fw-bold small text-dark mb-0">
                                    About / Status Text <span class="text-danger">*</span>
                                </label>
                                <span class="text-muted small" id="aboutCounter">0 / 139</span>
                            </div>
                            <input type="text" 
                                   name="about" 
                                   id="about" 
                                   maxlength="139" 
                                   class="form-control rounded-3 @error('about') is-invalid @enderror" 
                                   value="{{ old('about', $profileData['about'] ?? 'Pure, natural & Ayurvedic wellness crafted with love by Vanriti. 🌿') }}"
                                   oninput="document.getElementById('aboutCounter').innerText = this.value.length + ' / 139';"
                                   required>
                            <div class="form-text small text-muted">Displayed under your business name in contact info (max 139 characters).</div>
                            @error('about')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="description" class="form-label fw-bold small text-dark mb-0">
                                    Business Description (Bio)
                                </label>
                                <span class="text-muted small" id="descCounter">0 / 512</span>
                            </div>
                            <textarea name="description" 
                                      id="description" 
                                      rows="4" 
                                      maxlength="512" 
                                      class="form-control rounded-3 @error('description') is-invalid @enderror" 
                                      oninput="document.getElementById('descCounter').innerText = this.value.length + ' / 512';"
                                      placeholder="Introduce your brand, values, products, or service offerings...">{{ old('description', $profileData['description'] ?? 'Vanriti brings you pure, plant-powered Ayurvedic wellness, natural skincare, and holistic beauty rituals rooted in timeless Indian heritage.') }}</textarea>
                            <div class="form-text small text-muted">Comprehensive business summary shown in the WhatsApp Business details modal (max 512 characters).</div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Industry Vertical -->
                        <div class="mb-4">
                            <label for="vertical" class="form-label fw-bold small text-dark mb-1">
                                Business Category / Vertical <span class="text-danger">*</span>
                            </label>
                            <select name="vertical" id="vertical" class="form-select rounded-3 @error('vertical') is-invalid @enderror">
                                @foreach ($verticals as $vKey => $vLabel)
                                    <option value="{{ $vKey }}" {{ old('vertical', $profileData['vertical'] ?? 'BEAUTY') === $vKey ? 'selected' : '' }}>
                                        {{ $vLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small text-muted">Meta categorizes your business under this vertical in WhatsApp directory searches.</div>
                            @error('vertical')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Support Email & Website -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-bold small text-dark mb-1">
                                    Customer Support Email
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0 rounded-start-3"><i class="bi bi-envelope"></i></span>
                                    <input type="email" 
                                           name="email" 
                                           id="email" 
                                           class="form-control rounded-end-3 border-start-0 @error('email') is-invalid @enderror" 
                                           value="{{ old('email', $profileData['email'] ?? 'care@vanriti.com') }}" 
                                           placeholder="care@vanriti.com">
                                </div>
                                @error('email')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="website" class="form-label fw-bold small text-dark mb-1">
                                    Storefront Website URL
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0 rounded-start-3"><i class="bi bi-globe"></i></span>
                                    <input type="url" 
                                           name="website" 
                                           id="website" 
                                           class="form-control rounded-end-3 border-start-0 @error('website') is-invalid @enderror" 
                                           value="{{ old('website', (!empty($profileData['websites'][0])) ? $profileData['websites'][0] : url('/')) }}" 
                                           placeholder="https://vanriti.com">
                                </div>
                                @error('website')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Store Address -->
                        <div class="mb-4">
                            <label for="address" class="form-label fw-bold small text-dark mb-1">
                                Showroom / Physical Address
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0 rounded-start-3"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" 
                                       name="address" 
                                       id="address" 
                                       maxlength="256" 
                                       class="form-control rounded-end-3 border-start-0 @error('address') is-invalid @enderror" 
                                       value="{{ old('address', $profileData['address'] ?? 'Vanriti Wellness Studio, Tonk Road, Jaipur, Rajasthan 302015') }}" 
                                       placeholder="Full physical address or city">
                            </div>
                            @error('address')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2 pt-2">
                            <a href="{{ route('admin.whatsapp.inbox') }}" class="btn btn-outline-secondary rounded-pill px-4">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold d-flex align-items-center gap-2" style="background-color: #25D366; border-color: #25D366;">
                                <i class="bi bi-cloud-arrow-up-fill"></i> Save & Push to Meta
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Meta Business Policy & 24h Guidelines Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-light">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-lightbulb-fill text-warning"></i> Guidelines for High Quality Rating & Unlimited Messaging
                    </h6>
                    <div class="row g-3 small text-muted">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-check2-circle text-success fs-6 mt-0.5"></i>
                                <div>
                                    <strong class="text-dark">24-Hour Service Window:</strong>
                                    Free-form replies and attachments can be sent anytime within 24 hours of customer's message.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-check2-circle text-success fs-6 mt-0.5"></i>
                                <div>
                                    <strong class="text-dark">Initiating Chats:</strong>
                                    To message a customer outside 24 hours, use an approved Meta Template from the Live Inbox or Campaigns module.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-check2-circle text-success fs-6 mt-0.5"></i>
                                <div>
                                    <strong class="text-dark">Protect Quality Rating:</strong>
                                    Avoid blasting unwanted broadcasts. If customers block or report your number, Meta degrades your Quality Rating to Yellow/Red.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-check2-circle text-success fs-6 mt-0.5"></i>
                                <div>
                                    <strong class="text-dark">Tier Upgrades:</strong>
                                    As your broadcast volume grows while maintaining high read rates, Meta automatically elevates your limit from Tier 1K &rarr; Tier 10K &rarr; Tier 100K.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const aboutEl = document.getElementById('about');
        if (aboutEl) {
            document.getElementById('aboutCounter').innerText = aboutEl.value.length + ' / 139';
        }
        const descEl = document.getElementById('description');
        if (descEl) {
            document.getElementById('descCounter').innerText = descEl.value.length + ' / 512';
        }
    });
</script>
@endsection
