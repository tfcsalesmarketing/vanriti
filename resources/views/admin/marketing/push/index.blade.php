@extends('admin.layouts.app')

@section('title', 'Push Notifications Command Center')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="mb-0 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-bell-fill text-success"></i>
            <span>Push Notifications Command Center</span>
        </h4>
        <div class="text-muted small mt-0.5">Instant 1-click broadcasts, anonymous visitor reach, and automated abandoned cart push reminders.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#pushSettingsModal">
            <i class="bi bi-gear-fill"></i><span>Auto-Push Settings</span>
        </button>
        <button type="button" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 shadow-sm" onclick="document.getElementById('pushTitleInput').focus()">
            <i class="bi bi-send-fill"></i><span>Compose New Alert</span>
        </button>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>{{ session('warning') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Top KPI Summary Ribbon --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">Active Subscribers</span>
                    <span class="badge bg-success-subtle text-success rounded-pill p-2"><i class="bi bi-broadcast fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-success">{{ number_format($stats['active_subscribers']) }}</div>
                <div class="small text-muted" style="font-size:0.75rem;">{{ number_format($stats['anonymous_subscribers']) }} anonymous &middot; {{ number_format($stats['customer_subscribers']) }} customers</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">Mobile Devices</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill p-2"><i class="bi bi-phone-fill fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-dark">{{ number_format($stats['mobile_subscribers']) }}</div>
                <div class="small text-muted" style="font-size:0.75rem;">{{ number_format($stats['desktop_subscribers']) }} desktops / laptops</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">Alerts Delivered</span>
                    <span class="badge bg-info-subtle text-info rounded-pill p-2"><i class="bi bi-send-check-fill fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-primary">{{ number_format($stats['total_sent']) }}</div>
                <div class="small text-muted" style="font-size:0.75rem;">Across {{ number_format($stats['total_campaigns']) }} broadcasts</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">Click Rate (CTR)</span>
                    <span class="badge bg-warning-subtle text-warning rounded-pill p-2"><i class="bi bi-cursor-fill fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-warning-emphasis">{{ $stats['overall_ctr'] }}%</div>
                <div class="small text-muted" style="font-size:0.75rem;">{{ number_format($stats['total_clicks']) }} total link visits</div>
            </div>
        </div>
    </div>
</div>

{{-- Diagnostic & Admin Device Test Strip --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: #fafafa; border-left: 4px solid #15803d !important;">
    <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2.5">
            <div class="rounded-circle p-2 d-flex align-items-center justify-content-center bg-white shadow-xs text-success" style="width: 38px; height: 38px;">
                <i class="bi bi-shield-check fs-5"></i>
            </div>
            <div>
                <div class="fw-bold small text-dark d-flex align-items-center gap-2">
                    <span>Web Push Engine Status</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-2xs">Self-Hosted VAPID Active</span>
                    <span id="deviceSubBadge" class="badge bg-secondary-subtle text-secondary border rounded-pill font-2xs">Checking Device...</span>
                </div>
                <div class="text-muted font-2xs mt-0.5">
                    100% Free browser push notification protocol &middot; Zero monthly third-party subscriptions &middot; VAPID Subject: <code class="text-dark">care@vanriti.com</code>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="adminSubscribeDeviceBtn" class="btn btn-sm btn-outline-success fw-semibold d-inline-flex align-items-center gap-1.5 shadow-xs">
                <i class="bi bi-bell-plus"></i><span id="adminSubBtnText">Subscribe This Device (Test Alerts)</span>
            </button>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Left Column: 1-Click Broadcast Composer --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0 fw-bold">Broadcast Notification to Customers</h5>
                    <div class="text-muted small">Compose and blast instant push alerts to all subscriber devices in 1 click.</div>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill">
                    <i class="bi bi-lightning-charge-fill me-1"></i>Free &middot; Zero SMS Fees
                </span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.push.send') }}" id="pushBroadcastForm">
                    @csrf

                    {{-- Target Audience --}}
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase tracking-wider text-muted">Target Audience</label>
                        <div class="d-flex flex-wrap gap-2">
                            <label class="btn btn-outline-success btn-sm active d-flex align-items-center gap-1.5 py-2 px-3">
                                <input type="radio" name="target_audience" value="all" checked class="form-check-input mt-0 me-1">
                                <span>All Active Subscribers ({{ number_format($stats['active_subscribers']) }})</span>
                            </label>
                            <label class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1.5 py-2 px-3">
                                <input type="radio" name="target_audience" value="abandoned_cart" class="form-check-input mt-0 me-1">
                                <span>Shoppers with Items in Cart</span>
                            </label>
                            <label class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1.5 py-2 px-3">
                                <input type="radio" name="target_audience" value="customers" class="form-check-input mt-0 me-1">
                                <span>Registered Shoppers ({{ number_format($stats['customer_subscribers']) }})</span>
                            </label>
                        </div>
                    </div>

                    {{-- Title --}}
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small fw-bold mb-0" for="pushTitleInput">Notification Title <span class="text-danger">*</span></label>
                            <span class="text-muted font-2xs" id="pushTitleCounter">0 / 60</span>
                        </div>
                        <input type="text" name="title" id="pushTitleInput" class="form-control" placeholder="e.g. 🌿 Weekend Ayurvedic Flash Sale - 20% Off!" maxlength="60" required value="{{ old('title') }}">
                    </div>

                    {{-- Body --}}
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small fw-bold mb-0" for="pushBodyInput">Message Body <span class="text-danger">*</span></label>
                            <span class="text-muted font-2xs" id="pushBodyCounter">0 / 180</span>
                        </div>
                        <textarea name="body" id="pushBodyInput" class="form-control" rows="3" placeholder="e.g. Pure Bhringraj & Amla Hair Oils are back in stock. Enjoy free shipping with code WELCOME10 today only!" maxlength="180" required>{{ old('body') }}</textarea>
                    </div>

                    {{-- Destination URL --}}
                    <div class="mb-3">
                        <label class="form-label small fw-bold mb-1" for="pushUrlInput">Destination Link (Where to take them on click) <span class="text-danger">*</span></label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-link-45deg"></i></span>
                            <input type="url" name="url" id="pushUrlInput" class="form-control" placeholder="https://vanriti.com/shop" required value="{{ old('url', url('/shop')) }}">
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-1.5">
                            <span class="text-muted font-2xs me-1">Quick Presets:</span>
                            <button type="button" class="btn btn-xs btn-light border rounded-pill py-0.5 px-2 font-2xs js-url-preset" data-url="{{ url('/') }}">Homepage</button>
                            <button type="button" class="btn btn-xs btn-light border rounded-pill py-0.5 px-2 font-2xs js-url-preset" data-url="{{ url('/shop') }}">Shop All</button>
                            <button type="button" class="btn btn-xs btn-light border rounded-pill py-0.5 px-2 font-2xs js-url-preset" data-url="{{ url('/cart') }}">Shopping Bag</button>
                            <button type="button" class="btn btn-xs btn-light border rounded-pill py-0.5 px-2 font-2xs js-url-preset" data-url="{{ url('/category/hair-care') }}">Hair Care</button>
                            <button type="button" class="btn btn-xs btn-light border rounded-pill py-0.5 px-2 font-2xs js-url-preset" data-url="{{ url('/category/skin-care') }}">Skin Care</button>
                        </div>
                    </div>

                    {{-- Optional Banner Image --}}
                    <div class="mb-4">
                        <label class="form-label small fw-bold mb-1" for="pushImageInput">Promotional Banner Image URL <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="url" name="image_url" id="pushImageInput" class="form-control" placeholder="https://eduse-uploadedfiles.s3.ap-south-1.amazonaws.com/..." value="{{ old('image_url') }}">
                        <div class="form-text text-muted font-2xs">Displays an expansive image banner inside Android and desktop push alerts.</div>
                    </div>

                    {{-- Submit Actions --}}
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5" id="sendTestPushBtn">
                            <i class="bi bi-send"></i><span>Send Test to My Browser</span>
                        </button>
                        <button type="submit" class="btn btn-success fw-bold px-4 py-2 d-inline-flex align-items-center gap-2 shadow-sm" id="broadcastSubmitBtn" onclick="return confirm('Ready to broadcast this push notification to {{ number_format($stats['active_subscribers']) }} subscribers?');">
                            <i class="bi bi-broadcast"></i><span>Send Notification to All Customers</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Right Column: Live Mobile & Desktop Previews --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <span class="fw-bold small text-uppercase tracking-wider text-muted">Live Preview</span>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary active" id="previewMobileToggle"><i class="bi bi-phone me-1"></i>Mobile</button>
                    <button type="button" class="btn btn-outline-secondary" id="previewDesktopToggle"><i class="bi bi-laptop me-1"></i>Desktop</button>
                </div>
            </div>
            <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center bg-light rounded-bottom-3" style="min-height: 380px;">
                
                {{-- Smartphone Frame Preview --}}
                <div id="mobilePreviewFrame" class="w-100" style="max-width: 330px;">
                    <div class="p-3 bg-white rounded-4 shadow-sm border" style="border-color: #e2e8f0 !important;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-1.5">
                                <img src="{{ asset('images/logo.png') }}" alt="Vanriti" width="16" height="16" class="rounded">
                                <span class="fw-bold text-dark font-2xs" style="font-size: 0.72rem;">VANRITI</span>
                                <span class="text-muted font-2xs">&middot; now</span>
                            </div>
                            <i class="bi bi-app-indicator text-muted font-2xs"></i>
                        </div>
                        <div class="fw-bold text-dark small mb-1" id="previewMobileTitle">🌿 Weekend Ayurvedic Flash Sale - 20% Off!</div>
                        <div class="text-muted small mb-2 font-2xs" id="previewMobileBody" style="line-height: 1.35;">
                            Pure Bhringraj &amp; Amla Hair Oils are back in stock. Enjoy free shipping with code WELCOME10 today only!
                        </div>
                        <div id="previewMobileImageWrap" class="mb-2 d-none">
                            <img src="" id="previewMobileImage" class="img-fluid rounded-3 w-100" style="max-height: 140px; object-fit: cover;">
                        </div>
                        <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                            <span class="text-success fw-bold font-2xs">Tap to open shop &rarr;</span>
                            <span class="badge bg-light text-muted border font-2xs">Shop Now</span>
                        </div>
                    </div>
                </div>

                {{-- Desktop Toast Frame Preview (Initially Hidden) --}}
                <div id="desktopPreviewFrame" class="w-100 d-none" style="max-width: 360px;">
                    <div class="p-3 bg-white rounded-3 shadow border" style="border-left: 4px solid #15803d;">
                        <div class="d-flex align-items-start gap-2.5">
                            <img src="{{ asset('images/logo.png') }}" alt="Vanriti" width="38" height="38" class="rounded flex-shrink-0">
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="fw-bold text-dark small text-truncate" id="previewDesktopTitle">🌿 Weekend Ayurvedic Flash Sale!</div>
                                    <span class="text-muted font-2xs">vanriti.com</span>
                                </div>
                                <div class="text-muted small mt-1 font-2xs" id="previewDesktopBody" style="line-height: 1.35;">
                                    Pure Bhringraj &amp; Amla Hair Oils are back in stock. Enjoy free shipping today!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-muted text-center font-2xs mt-3">
                    <i class="bi bi-shield-check text-success me-1"></i>Delivered via Chrome, Safari, Edge, Android &amp; Windows Notification Service
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Broadcast Campaign History Table --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-bold">Broadcast Campaigns &amp; Delivery Stats</h5>
            <div class="text-muted small">Real-time delivery confirmation and recipient click-through performance.</div>
        </div>
        @if ($campaigns->count() > 0)
        <div>
            <button type="button" class="btn btn-sm btn-outline-danger fw-semibold d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#cleanupCampaignsModal">
                <i class="bi bi-trash"></i><span>Delete Older Notifications</span>
            </button>
        </div>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase font-2xs text-muted">
                <tr>
                    <th class="ps-4">Campaign Title &amp; URL</th>
                    <th>Target Audience</th>
                    <th>Sent At</th>
                    <th class="text-center">Targeted</th>
                    <th class="text-center">Delivered</th>
                    <th class="text-center">Clicks</th>
                    <th class="text-center">CTR</th>
                    <th class="text-center">Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($campaigns as $camp)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark">{{ $camp->title }}</div>
                            <div class="text-muted small text-truncate" style="max-width: 280px;">{{ $camp->body }}</div>
                            <a href="{{ $camp->url }}" target="_blank" class="font-2xs text-success text-decoration-none d-inline-flex align-items-center gap-1 mt-0.5">
                                <span>{{ $camp->url }}</span><i class="bi bi-box-arrow-up-right font-2xs"></i>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $camp->target_audience)) }}</span>
                        </td>
                        <td>
                            <div class="small fw-semibold">{{ $camp->sent_at ? $camp->sent_at->format('d M Y, h:i A') : 'Draft' }}</div>
                            <div class="text-muted font-2xs">{{ $camp->sender?->name ?? 'Admin' }}</div>
                        </td>
                        <td class="text-center fw-semibold">{{ number_format($camp->total_targeted) }}</td>
                        <td class="text-center text-success fw-bold">{{ number_format($camp->total_sent) }}</td>
                        <td class="text-center text-primary fw-bold">{{ number_format($camp->total_clicked) }}</td>
                        <td class="text-center">
                            <span class="badge bg-warning-subtle text-warning-emphasis">{{ $camp->click_through_rate }}%</span>
                        </td>
                        <td class="text-center">
                            @if ($camp->status === 'completed')
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Completed</span>
                            @elseif ($camp->status === 'sending')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Sending...</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill">{{ ucfirst($camp->status) }}</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <form method="POST" action="{{ route('admin.push.campaigns.destroy', $camp) }}" class="d-inline" onsubmit="return confirm('Delete push notification campaign &quot;{{ addslashes($camp->title) }}&quot;?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2 font-2xs rounded" title="Delete this campaign">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-broadcast fs-2 d-block mb-2 text-muted"></i>
                            <h6>No push notification campaigns sent yet</h6>
                            <p class="small mb-0">Use the composer above to broadcast your first offer to all active subscribers.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($campaigns->hasPages())
        <div class="card-footer bg-white py-2 px-4 border-top">
            {{ $campaigns->links() }}
        </div>
    @endif
</div>

{{-- Settings Modal --}}
<div class="modal fade" id="pushSettingsModal" tabindex="-1" aria-labelledby="pushSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('admin.push.settings.update') }}">
                @csrf
                <div class="modal-header border-bottom py-3 px-4">
                    <h5 class="modal-title fw-bold" id="pushSettingsModalLabel">Auto-Push &amp; VAPID Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="push_abandoned_cart_enabled" value="1" id="autoPushSwitch" @checked($autoPushEnabled)>
                        <label class="form-check-label fw-bold small" for="autoPushSwitch">
                            Enable Automated Abandoned Cart Push Reminders
                        </label>
                        <div class="form-text text-muted font-2xs">
                            Automatically triggers a push notification to anonymous and registered visitors who left items in their cart.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" for="autoPushDelayInput">Trigger Delay (Minutes after cart abandonment)</label>
                        <input type="number" name="push_abandoned_cart_delay_minutes" id="autoPushDelayInput" class="form-control form-control-sm" value="{{ $autoPushDelay }}" min="5" max="1440" required>
                        <div class="form-text text-muted font-2xs">Recommended: 30–60 minutes.</div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border">
                        <div class="small fw-bold text-dark mb-1">Server VAPID Public Key:</div>
                        <input type="text" class="form-control form-control-sm bg-white font-monospace" readonly value="{{ $keys['publicKey'] ?? 'Configured' }}" style="font-size: 0.7rem;">
                        <div class="small text-muted mt-1 font-2xs">Used by customer browsers to securely encrypt notifications.</div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold px-3">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Cleanup / Delete Older Campaigns Modal --}}
<div class="modal fade" id="cleanupCampaignsModal" tabindex="-1" aria-labelledby="cleanupCampaignsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('admin.push.campaigns.cleanup') }}">
                @csrf
                <div class="modal-header border-bottom py-3 px-4">
                    <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="cleanupCampaignsModalLabel">
                        <i class="bi bi-trash-fill"></i>
                        <span>Delete Older Push Notifications</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Choose which past push notification campaigns you would like to permanently remove from history:
                    </p>
                    <div class="list-group mb-3">
                        <label class="list-group-item d-flex gap-2.5 cursor-pointer py-3">
                            <input class="form-check-input flex-shrink-0 mt-0.5" type="radio" name="scope" value="older_30" checked>
                            <div>
                                <div class="fw-semibold small">Campaigns Older Than 30 Days</div>
                                <div class="text-muted font-2xs">Safely purge older campaigns while keeping the last month of analytics.</div>
                            </div>
                        </label>
                        <label class="list-group-item d-flex gap-2.5 cursor-pointer py-3">
                            <input class="form-check-input flex-shrink-0 mt-0.5" type="radio" name="scope" value="older_7">
                            <div>
                                <div class="fw-semibold small">Campaigns Older Than 7 Days</div>
                                <div class="text-muted font-2xs">Keep only this week's campaigns and delete everything older.</div>
                            </div>
                        </label>
                        <label class="list-group-item d-flex gap-2.5 cursor-pointer py-3">
                            <input class="form-check-input flex-shrink-0 mt-0.5" type="radio" name="scope" value="failed">
                            <div>
                                <div class="fw-semibold small">Failed Campaigns Only</div>
                                <div class="text-muted font-2xs">Remove campaigns that failed to deliver or had 0 recipients.</div>
                            </div>
                        </label>
                        <label class="list-group-item d-flex gap-2.5 cursor-pointer py-3 bg-danger-subtle border-danger-subtle">
                            <input class="form-check-input flex-shrink-0 mt-0.5 text-danger" type="radio" name="scope" value="all">
                            <div>
                                <div class="fw-semibold small text-danger">Entire Campaign History (Delete All)</div>
                                <div class="text-muted font-2xs">Permanently delete all push broadcast logs and reset history to zero.</div>
                            </div>
                        </label>
                    </div>
                    <div class="alert alert-warning font-2xs py-2 px-3 mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        This action will delete the broadcast logs and delivery analytics. Active subscriber devices will remain subscribed.
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-bold px-3">Confirm &amp; Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var titleInput = document.getElementById('pushTitleInput');
    var bodyInput = document.getElementById('pushBodyInput');
    var imageInput = document.getElementById('pushImageInput');
    var urlInput = document.getElementById('pushUrlInput');

    var previewMobileTitle = document.getElementById('previewMobileTitle');
    var previewMobileBody = document.getElementById('previewMobileBody');
    var previewMobileImageWrap = document.getElementById('previewMobileImageWrap');
    var previewMobileImage = document.getElementById('previewMobileImage');

    var previewDesktopTitle = document.getElementById('previewDesktopTitle');
    var previewDesktopBody = document.getElementById('previewDesktopBody');

    var mobileFrame = document.getElementById('mobilePreviewFrame');
    var desktopFrame = document.getElementById('desktopPreviewFrame');
    var mobileToggle = document.getElementById('previewMobileToggle');
    var desktopToggle = document.getElementById('previewDesktopToggle');

    function updatePreview() {
        var title = titleInput.value.trim() || '🌿 Weekend Ayurvedic Flash Sale - 20% Off!';
        var body = bodyInput.value.trim() || 'Pure Bhringraj & Amla Hair Oils are back in stock. Enjoy free shipping today!';
        var img = imageInput.value.trim();

        if (previewMobileTitle) previewMobileTitle.textContent = title;
        if (previewDesktopTitle) previewDesktopTitle.textContent = title;

        if (previewMobileBody) previewMobileBody.textContent = body;
        if (previewDesktopBody) previewDesktopBody.textContent = body;

        document.getElementById('pushTitleCounter').textContent = titleInput.value.length + ' / 60';
        document.getElementById('pushBodyCounter').textContent = bodyInput.value.length + ' / 180';

        if (img) {
            previewMobileImage.src = img;
            previewMobileImageWrap.classList.remove('d-none');
        } else {
            previewMobileImageWrap.classList.add('d-none');
        }
    }

    if (titleInput) titleInput.addEventListener('input', updatePreview);
    if (bodyInput) bodyInput.addEventListener('input', updatePreview);
    if (imageInput) imageInput.addEventListener('input', updatePreview);

    // Quick presets for URL
    document.querySelectorAll('.js-url-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (urlInput) {
                urlInput.value = btn.dataset.url;
            }
        });
    });

    // Preview toggles
    if (mobileToggle && desktopToggle) {
        mobileToggle.addEventListener('click', function () {
            mobileToggle.classList.add('active');
            desktopToggle.classList.remove('active');
            mobileFrame.classList.remove('d-none');
            desktopFrame.classList.add('d-none');
        });

        desktopToggle.addEventListener('click', function () {
            desktopToggle.classList.add('active');
            mobileToggle.classList.remove('active');
            desktopFrame.classList.remove('d-none');
            mobileFrame.classList.add('d-none');
        });
    }

    // Dynamic config using Blade route helpers for full environment compatibility
    var VR_ADMIN_PUSH_CONFIG = {
        publicKeyUrl: "{{ route('push.public-key') }}",
        subscribeUrl: "{{ route('push.subscribe') }}",
        swUrl: "{{ url('sw.js') }}",
        scopeUrl: "{{ url('/') }}/",
        testUrl: "{{ route('admin.push.test') }}",
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "{{ csrf_token() }}"
    };

    // Helper: convert url-safe base64 to Uint8Array
    function urlB64ToUint8Array(base64String) {
        base64String = (base64String || '').trim();
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);

        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Admin Device Web Push Subscription & Diagnostics Routine
    var deviceBadge = document.getElementById('deviceSubBadge');
    var adminSubBtn = document.getElementById('adminSubscribeDeviceBtn');
    var adminSubBtnText = document.getElementById('adminSubBtnText');
    var sendTestBtn = document.getElementById('sendTestPushBtn');
    var currentDeviceSubscriptionId = null;

    function checkDeviceStatus() {
        if (!window.isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window)) {
            if (deviceBadge) {
                deviceBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill font-2xs';
                deviceBadge.textContent = 'Insecure Context (Requires HTTPS or localhost)';
            }
            if (adminSubBtn) adminSubBtn.disabled = true;
            return Promise.resolve(null);
        }

        if (Notification.permission === 'granted') {
            return navigator.serviceWorker.register(VR_ADMIN_PUSH_CONFIG.swUrl, { scope: VR_ADMIN_PUSH_CONFIG.scopeUrl })
                .then(function () { return navigator.serviceWorker.ready; })
                .then(function (reg) {
                    return reg.pushManager.getSubscription();
                })
                .then(function (sub) {
                    if (sub) {
                        if (deviceBadge) {
                            deviceBadge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill font-2xs';
                            deviceBadge.innerHTML = '<i class="bi bi-check2-circle me-1"></i>This Device Subscribed &amp; Ready';
                        }
                        if (adminSubBtn) {
                            adminSubBtn.className = 'btn btn-sm btn-light border text-muted font-2xs';
                            adminSubBtnText.textContent = 'Device Registered (Re-sync)';
                        }
                        return sub;
                    } else {
                        if (deviceBadge) {
                            deviceBadge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill font-2xs';
                            deviceBadge.textContent = 'Permission Granted, Device Not Subscribed';
                        }
                        if (adminSubBtn) {
                            adminSubBtn.className = 'btn btn-sm btn-outline-success font-2xs';
                            adminSubBtnText.textContent = 'Subscribe This Device Now';
                        }
                        return null;
                    }
                })
                .catch(function (err) {
                    console.warn('checkDeviceStatus error:', err);
                    return null;
                });
        } else if (Notification.permission === 'denied') {
            if (deviceBadge) {
                deviceBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill font-2xs';
                deviceBadge.textContent = 'Notifications Blocked in Browser';
            }
            if (adminSubBtn) {
                adminSubBtn.className = 'btn btn-sm btn-outline-danger font-2xs';
                adminSubBtnText.textContent = 'Unblock in Browser URL Bar';
            }
            return Promise.resolve(null);
        } else {
            if (deviceBadge) {
                deviceBadge.className = 'badge bg-secondary-subtle text-secondary border rounded-pill font-2xs';
                deviceBadge.textContent = 'Not Subscribed on This Device';
            }
            if (adminSubBtn) {
                adminSubBtn.className = 'btn btn-sm btn-outline-success font-2xs';
                adminSubBtnText.textContent = 'Subscribe This Device';
            }
            return Promise.resolve(null);
        }
    }

    checkDeviceStatus();

    // Reusable routine to subscribe device and store token in database
    function registerDeviceSubscription() {
        return Notification.requestPermission().then(function (perm) {
            if (perm !== 'granted') {
                throw new Error('Notification permission was ' + perm + '. Please allow notifications in browser prompt.');
            }

            return fetch(VR_ADMIN_PUSH_CONFIG.publicKeyUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status + ' fetching public VAPID key');
                    return res.json();
                })
                .then(function (keyData) {
                    if (!keyData || !keyData.publicKey) {
                        throw new Error('VAPID public key unavailable from server');
                    }

                    return navigator.serviceWorker.register(VR_ADMIN_PUSH_CONFIG.swUrl, { scope: VR_ADMIN_PUSH_CONFIG.scopeUrl })
                        .then(function () { return navigator.serviceWorker.ready; })
                        .then(function (reg) {
                            return reg.pushManager.getSubscription().then(function (existingSub) {
                                if (existingSub) {
                                    return existingSub;
                                }
                                return reg.pushManager.subscribe({
                                    userVisibleOnly: true,
                                    applicationServerKey: urlB64ToUint8Array(keyData.publicKey)
                                });
                            });
                        });
                })
                .then(function (sub) {
                    var subJson = sub.toJSON();
                    return fetch(VR_ADMIN_PUSH_CONFIG.subscribeUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': VR_ADMIN_PUSH_CONFIG.csrfToken
                        },
                        body: JSON.stringify(subJson)
                    });
                })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status + ' storing subscription');
                    return res.json();
                })
                .then(function (data) {
                    if (data && data.subscription_id) {
                        currentDeviceSubscriptionId = data.subscription_id;
                    }
                    checkDeviceStatus();
                    return data;
                });
        });
    }

    if (adminSubBtn) {
        adminSubBtn.addEventListener('click', function () {
            adminSubBtn.disabled = true;
            adminSubBtnText.textContent = 'Subscribing Device...';

            registerDeviceSubscription()
                .then(function () {
                    adminSubBtn.disabled = false;
                    alert('Success! Your admin device is now subscribed. You can now send instant test alerts to this screen.');
                })
                .catch(function (err) {
                    adminSubBtn.disabled = false;
                    console.error(err);
                    alert('Device subscription error: ' + err.message);
                    checkDeviceStatus();
                });
        });
    }

    // Send Test push handler (Seamless AJAX with auto-subscribe & instant onscreen alert)
    if (sendTestBtn) {
        sendTestBtn.addEventListener('click', function () {
            var title = (titleInput && titleInput.value.trim()) || '';
            var body = (bodyInput && bodyInput.value.trim()) || '';
            var url = (urlInput && urlInput.value.trim()) || '';

            if (!title) {
                alert('Please enter a Notification Title first.');
                if (titleInput) titleInput.focus();
                return;
            }
            if (!body) {
                alert('Please enter a Notification Message first.');
                if (bodyInput) bodyInput.focus();
                return;
            }
            if (!url) {
                url = window.location.origin;
            }

            var originalHtml = sendTestBtn.innerHTML;
            sendTestBtn.disabled = true;
            sendTestBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span><span>Preparing Test Alert...</span>';

            // Check if device is already subscribed, or subscribe automatically
            registerDeviceSubscription()
                .catch(function (subErr) {
                    console.warn('Direct registration failed or permission prompt needed:', subErr);
                    // Continue attempt with existing subscription in DB if present
                    return null;
                })
                .then(function () {
                    sendTestBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span><span>Dispatching Push...</span>';

                    var payload = {
                        title: title,
                        body: body,
                        url: url,
                        subscription_id: currentDeviceSubscriptionId,
                        _token: VR_ADMIN_PUSH_CONFIG.csrfToken
                    };

                    return fetch(VR_ADMIN_PUSH_CONFIG.testUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': VR_ADMIN_PUSH_CONFIG.csrfToken
                        },
                        body: JSON.stringify(payload)
                    });
                })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function (result) {
                    sendTestBtn.disabled = false;
                    sendTestBtn.innerHTML = originalHtml;

                    if (result.ok && result.data && result.data.success) {
                        alert('🎉 ' + result.data.message);
                    } else {
                        var errMsg = (result.data && result.data.message) ? result.data.message : 'Failed to send test push notification.';
                        alert('⚠️ ' + errMsg);
                    }
                })
                .catch(function (err) {
                    sendTestBtn.disabled = false;
                    sendTestBtn.innerHTML = originalHtml;
                    console.error('Test push dispatch error:', err);
                    alert('Error dispatching test push: ' + err.message);
                });
        });
    }
});
</script>
@endsection

