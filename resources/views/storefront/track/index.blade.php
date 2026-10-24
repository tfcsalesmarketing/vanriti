@extends('storefront.layouts.app')
@section('title', 'Track Your Order | ' . store_name())
@section('meta_description', 'Track your Vanriti luxury order in real time with live courier checkpoints and estimated delivery updates.')
@section('robots', 'noindex, follow')

@section('content')
<div class="vr-section py-4 py-md-5" style="background: linear-gradient(180deg, #F9FAF8 0%, #FFFFFF 100%); min-height: 80vh;">
    <div class="container" style="max-width: 960px;">
        
        {{-- Section Header --}}
        <div class="text-center mb-4">
            <span class="badge rounded-pill px-3 py-1 mb-2" style="background: #EBF3ED; color: #1C3C24; font-size: 0.78rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase;">
                <i class="bi bi-geo-alt-fill me-1" style="color: #10B981;"></i> LIVE TRACKING
            </span>
            <h1 class="vr-section-title mb-2" style="font-size: 2rem; font-weight: 700; color: #111827;">Track Your Order</h1>
            <p class="text-muted small mx-auto" style="max-width: 520px; font-size: 0.92rem;">
                Stay updated with live real-time carrier checkpoints, estimated delivery dates, and dispatch status.
            </p>
        </div>

        {{-- Order Search Form --}}
        <div class="row justify-content-center mb-4">
            <div class="col-12 col-md-10 col-lg-8">
                <div class="card border-0 shadow-sm" style="border-radius: 16px; background: #FFFFFF; border: 1px solid rgba(28, 60, 36, 0.08) !important;">
                    <div class="card-body p-3 p-md-4">
                        <form action="{{ route('track.lookup') }}" method="POST" novalidate id="trackLookupForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label small fw-semibold text-dark mb-1">
                                        Order # or AWB Number
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 10px 0 0 10px;">
                                            <i class="bi bi-box-seam"></i>
                                        </span>
                                        <input type="text" name="order_number" class="form-control border-start-0 ps-0"
                                               placeholder="e.g. VAN-2026-000001 or AWB"
                                               value="{{ old('order_number', request('order_number', $orders?->first()?->order_number)) }}"
                                               style="border-radius: 0 10px 10px 0; font-size: 0.9rem;" required>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label small fw-semibold text-dark mb-1">
                                        Mobile Number
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 10px 0 0 10px;">
                                            <span style="font-size: 0.8rem; font-weight: 600;">+91</span>
                                        </span>
                                        <input type="tel" name="mobile" class="form-control border-start-0 ps-1"
                                               placeholder="10-digit mobile number"
                                               value="{{ old('mobile', request('mobile', $orders?->first()?->shipping_mobile ?: $orders?->first()?->billing_mobile)) }}"
                                               maxlength="10" inputmode="numeric"
                                               style="border-radius: 0 10px 10px 0; font-size: 0.9rem;" required>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn w-100 py-2.5 text-white fw-semibold d-flex align-items-center justify-content-center gap-2"
                                            style="background: #1C3C24; border-radius: 10px; font-size: 0.92rem; transition: all 0.2s ease;">
                                        <i class="bi bi-search"></i>
                                        <span>Track My Parcel</span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        {{-- Quick Pills for Recent Orders if Logged In --}}
                        @if (isset($recentOrders) && $recentOrders->isNotEmpty())
                            <div class="pt-3 mt-3 border-top d-flex flex-wrap align-items-center gap-1.5" style="border-color: #F3F4F6 !important;">
                                <span class="text-muted small me-1" style="font-size: 0.78rem;">Recent Orders:</span>
                                @foreach ($recentOrders as $ro)
                                    <a href="{{ route('track.order', ['order' => $ro->order_number, 'mobile' => $ro->shipping_mobile ?: $ro->billing_mobile]) }}"
                                       class="badge text-decoration-none border text-dark fw-medium px-2.5 py-1.5"
                                       style="background: #F9FAFB; font-size: 0.76rem; border-radius: 6px;">
                                        {{ $ro->order_number }}
                                        <span class="text-muted ms-1" style="font-size: 0.7rem;">({{ ucwords(str_replace('_', ' ', $ro->order_status)) }})</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Tracking Results View --}}
        @if ($orders !== null && $orders->count() > 0)
            @foreach ($orders as $order)
                @php
                    $shipment = $order->shipments->sortByDesc('created_at')->first();
                    $trackingEvents = $shipment ? $shipment->trackingEvents->sortByDesc('event_date') : collect();
                    $status = strtolower($order->order_status);
                    $shipmentStatus = strtolower((string) ($shipment?->status ?? ''));
                    $isDelivered = in_array($status, ['delivered'], true) || in_array($shipmentStatus, ['delivered'], true);
                    $isCancelled = in_array($status, ['cancelled', 'failed'], true);
                    $isOutOfDelivery = in_array($status, ['out_for_delivery'], true) || in_array($shipmentStatus, ['out_for_delivery'], true);
                    $isShipped = in_array($status, ['shipped'], true) || in_array($shipmentStatus, ['shipped'], true) || !empty($shipment?->awb_number);
                    $isPacked = in_array($status, ['packed', 'ready_to_ship'], true) || in_array($shipmentStatus, ['packed'], true);

                    // Stepper active index (0 to 4)
                    $stepIndex = 0; // Order Confirmed
                    if ($isDelivered) {
                        $stepIndex = 4;
                    } elseif ($isOutOfDelivery) {
                        $stepIndex = 3;
                    } elseif ($isShipped) {
                        $stepIndex = 2;
                    } elseif ($isPacked) {
                        $stepIndex = 1;
                    }

                    // Progress bar percent
                    $progressPercent = match($stepIndex) {
                        0 => 12,
                        1 => 35,
                        2 => 65,
                        3 => 88,
                        4 => 100,
                        default => 12,
                    };
                    if ($isCancelled) {
                        $progressPercent = 100;
                    }

                    $courierName = $shipment?->courier ?: 'ShipMojo Express';
                    $awb = $shipment?->awb_number ?: $shipment?->tracking_number;
                    $estDelivery = $shipment?->estimated_delivery;
                    $deliveredAt = $shipment?->delivered_at ?: ($isDelivered ? $order->updated_at : null);
                @endphp

                <div class="row justify-content-center mb-5">
                    <div class="col-12 col-md-10 col-lg-9">
                        
                        {{-- ── Main Tracking Container ── --}}
                        <div class="card border-0 shadow-sm overflow-hidden mb-4" style="border-radius: 18px; border: 1px solid rgba(28, 60, 36, 0.08) !important;">
                            
                            {{-- Card Header: Order Number & Live Status --}}
                            <div class="card-header bg-white p-3 p-md-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-color: #F1F5F9 !important;">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h5 class="mb-0 fw-bold text-dark font-monospace" style="font-size: 1.15rem;">{{ $order->order_number }}</h5>
                                        @if ($isDelivered)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> Delivered
                                            </span>
                                        @elseif ($isCancelled)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">
                                                <i class="bi bi-x-circle-fill me-1"></i> Cancelled
                                            </span>
                                        @elseif ($isOutOfDelivery)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1">
                                                <i class="bi bi-bicycle me-1"></i> Out for Delivery
                                            </span>
                                        @elseif ($isShipped)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1">
                                                <i class="bi bi-truck me-1"></i> In Transit
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1">
                                                <i class="bi bi-clock-history me-1"></i> Order Confirmed
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 0.8rem;">
                                        Placed on {{ $order->created_at->format('d M Y, h:i A') }}
                                        &middot; {{ $order->items->count() }} item(s) &middot; {{ format_price($order->grand_total) }}
                                    </div>
                                </div>

                                {{-- Live Re-fetch / Refresh Status Button --}}
                                @if (! $isCancelled && ! $isDelivered)
                                    <a href="{{ request()->fullUrlWithQuery(['refresh' => 1]) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5" style="border-radius: 8px; font-size: 0.78rem;">
                                        <i class="bi bi-arrow-clockwise"></i>
                                        <span>Refresh Live Status</span>
                                    </a>
                                @endif
                            </div>

                            <div class="card-body p-3 p-md-4">

                                {{-- ── HERO DELIVERY STATUS BANNER ── --}}
                                @if ($isCancelled)
                                    <div class="p-3.5 rounded-3 mb-4 d-flex align-items-center gap-3" style="background: #FEF2F2; border: 1px solid #FEE2E2;">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-danger" style="width: 48px; height: 48px; background: #FEE2E2; flex-shrink: 0; font-size: 1.4rem;">
                                            <i class="bi bi-x-octagon-fill"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-danger mb-0.5">Order Cancelled</h6>
                                            <div class="text-muted small" style="font-size: 0.84rem;">
                                                This order has been cancelled. If any payment was captured, the refund will be credited back to your original source.
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="p-3.5 p-md-4 rounded-3 mb-4" style="background: linear-gradient(135deg, #F0FDF4 0%, #FFFFFF 100%); border: 1px solid #D1FAE5;">
                                        <div class="row align-items-center g-3">
                                            <div class="col-12 col-md-7">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                         style="width: 52px; height: 52px; background: #1C3C24; color: #FFFFFF; flex-shrink: 0; font-size: 1.5rem; box-shadow: 0 4px 12px rgba(28, 60, 36, 0.2);">
                                                        @if ($isDelivered)
                                                            <i class="bi bi-house-check-fill" style="color: #34D399;"></i>
                                                        @elseif ($isOutOfDelivery)
                                                            <i class="bi bi-geo-alt-fill text-warning"></i>
                                                        @elseif ($isShipped)
                                                            <i class="bi bi-truck" style="color: #6EE7B7;"></i>
                                                        @else
                                                            <i class="bi bi-box-seam" style="color: #FDE68A;"></i>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                                            @if ($isDelivered)
                                                                DELIVERY COMPLETE
                                                            @elseif ($isOutOfDelivery)
                                                                OUT FOR DELIVERY TODAY
                                                            @elseif ($isShipped)
                                                                SHIPMENT IN TRANSIT
                                                            @else
                                                                ORDER UNDER PREPARATION
                                                            @endif
                                                        </div>
                                                        <h5 class="fw-bold text-dark mb-0" style="font-size: 1.25rem;">
                                                            @if ($isDelivered)
                                                                Delivered on {{ $deliveredAt ? $deliveredAt->format('d M Y') : 'Recently' }}
                                                            @elseif ($estDelivery)
                                                                Expected Delivery by <span style="color: #059669;">{{ Carbon\Carbon::parse($estDelivery)->format('l, d M Y') }}</span>
                                                            @elseif ($isOutOfDelivery)
                                                                Arriving Today by Evening
                                                            @elseif ($isShipped)
                                                                On Its Way to Your Doorstep
                                                            @else
                                                                Preparing Your Luxury Package
                                                            @endif
                                                        </h5>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Carrier Partner & AWB Copy Bar --}}
                                            <div class="col-12 col-md-5 text-md-end">
                                                @if ($awb)
                                                    <div class="d-inline-block text-md-end p-2 px-3 rounded-3" style="background: rgba(255, 255, 255, 0.9); border: 1px solid #E5E7EB;">
                                                        <div class="d-flex align-items-center justify-content-md-end gap-1.5 small mb-0.5">
                                                            <span class="badge bg-dark-subtle text-dark border px-2 py-0.5 rounded-pill" style="font-size: 0.7rem;">
                                                                <i class="bi bi-truck me-1"></i>{{ $courierName }}
                                                            </span>
                                                        </div>
                                                        <div class="d-flex align-items-center justify-content-md-end gap-2">
                                                            <span class="small font-monospace fw-bold text-dark" id="awbDisplay">{{ $awb }}</span>
                                                            <button type="button" class="btn btn-sm btn-light border p-0 px-2 d-inline-flex align-items-center gap-1"
                                                                    id="copyAwbBtn" onclick="copyAwbToClipboard('{{ $awb }}')" title="Copy Tracking Number" style="font-size: 0.72rem; height: 26px;">
                                                                <i class="bi bi-copy"></i>
                                                                <span id="copyAwbText">Copy</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted small">Tracking AWB will be updated upon carrier dispatch</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ── 5-STAGE INTERACTIVE PROGRESS STEPPER BAR ── --}}
                                    <div class="mb-5 px-2 px-md-3">
                                        <div class="position-relative">
                                            {{-- Background line --}}
                                            <div class="position-absolute top-50 start-0 translate-middle-y w-100" style="height: 6px; background: #E5E7EB; border-radius: 6px; z-index: 1;"></div>
                                            {{-- Filled progress line --}}
                                            <div class="position-absolute top-50 start-0 translate-middle-y"
                                                 style="height: 6px; width: {{ $progressPercent }}%; background: linear-gradient(90deg, #1C3C24 0%, #10B981 100%); border-radius: 6px; z-index: 2; transition: width 0.8s ease;"></div>

                                            {{-- 5 Steps --}}
                                            <div class="d-flex justify-content-between position-relative" style="z-index: 3;">
                                                
                                                {{-- Step 1: Confirmed --}}
                                                <div class="text-center" style="width: 60px;">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2"
                                                         style="width: 36px; height: 36px; background: {{ $stepIndex >= 0 ? '#1C3C24' : '#E5E7EB' }}; color: #FFFFFF; font-size: 0.95rem; border: 3px solid #FFFFFF; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                        <i class="bi bi-bag-check-fill"></i>
                                                    </div>
                                                    <div class="fw-semibold text-dark" style="font-size: 0.75rem; line-height: 1.2;">Confirmed</div>
                                                    <div class="text-muted" style="font-size: 0.68rem;">{{ $order->created_at->format('d M') }}</div>
                                                </div>

                                                {{-- Step 2: Packed --}}
                                                <div class="text-center" style="width: 60px;">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 {{ $stepIndex === 1 ? 'vr-step-pulse' : '' }}"
                                                         style="width: 36px; height: 36px; background: {{ $stepIndex >= 1 ? '#1C3C24' : '#E5E7EB' }}; color: {{ $stepIndex >= 1 ? '#FFFFFF' : '#9CA3AF' }}; font-size: 0.95rem; border: 3px solid #FFFFFF; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                        <i class="bi bi-box-seam-fill"></i>
                                                    </div>
                                                    <div class="fw-semibold {{ $stepIndex >= 1 ? 'text-dark' : 'text-muted' }}" style="font-size: 0.75rem; line-height: 1.2;">Packed</div>
                                                    <div class="text-muted" style="font-size: 0.68rem;">Ready</div>
                                                </div>

                                                {{-- Step 3: In Transit --}}
                                                <div class="text-center" style="width: 60px;">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 {{ $stepIndex === 2 ? 'vr-step-pulse' : '' }}"
                                                         style="width: 36px; height: 36px; background: {{ $stepIndex >= 2 ? '#10B981' : '#E5E7EB' }}; color: {{ $stepIndex >= 2 ? '#FFFFFF' : '#9CA3AF' }}; font-size: 0.95rem; border: 3px solid #FFFFFF; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                        <i class="bi bi-truck"></i>
                                                    </div>
                                                    <div class="fw-semibold {{ $stepIndex >= 2 ? 'text-dark' : 'text-muted' }}" style="font-size: 0.75rem; line-height: 1.2;">In Transit</div>
                                                    <div class="text-muted" style="font-size: 0.68rem;">Dispatched</div>
                                                </div>

                                                {{-- Step 4: Out for Delivery --}}
                                                <div class="text-center" style="width: 60px;">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 {{ $stepIndex === 3 ? 'vr-step-pulse' : '' }}"
                                                         style="width: 36px; height: 36px; background: {{ $stepIndex >= 3 ? '#F59E0B' : '#E5E7EB' }}; color: {{ $stepIndex >= 3 ? '#FFFFFF' : '#9CA3AF' }}; font-size: 0.95rem; border: 3px solid #FFFFFF; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                        <i class="bi bi-geo-alt-fill"></i>
                                                    </div>
                                                    <div class="fw-semibold {{ $stepIndex >= 3 ? 'text-dark' : 'text-muted' }}" style="font-size: 0.75rem; line-height: 1.2;">Out for Delivery</div>
                                                    <div class="text-muted" style="font-size: 0.68rem;">Local Hub</div>
                                                </div>

                                                {{-- Step 5: Delivered --}}
                                                <div class="text-center" style="width: 60px;">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 {{ $stepIndex === 4 ? 'vr-step-pulse' : '' }}"
                                                         style="width: 36px; height: 36px; background: {{ $stepIndex >= 4 ? '#059669' : '#E5E7EB' }}; color: {{ $stepIndex >= 4 ? '#FFFFFF' : '#9CA3AF' }}; font-size: 0.95rem; border: 3px solid #FFFFFF; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                        <i class="bi bi-house-check-fill"></i>
                                                    </div>
                                                    <div class="fw-semibold {{ $stepIndex >= 4 ? 'text-dark' : 'text-muted' }}" style="font-size: 0.75rem; line-height: 1.2;">Delivered</div>
                                                    <div class="text-muted" style="font-size: 0.68rem;">Complete</div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- ── TWO COLUMN DETAILS: Carrier Checkpoints & Order Summary ── --}}
                                <div class="row g-4">
                                    
                                    {{-- LEFT: Live Carrier Scan Journey Timeline --}}
                                    <div class="col-12 col-lg-7">
                                        <div class="p-3.5 rounded-3 bg-white h-100 border" style="border-color: #F1F5F9 !important;">
                                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                                                    <i class="bi bi-clock-history text-success"></i>
                                                    <span>Live Checkpoints</span>
                                                </h6>
                                                @if ($order->externalTrackingUrl())
                                                    <a href="{{ $order->externalTrackingUrl() }}" target="_blank" class="small text-decoration-none fw-semibold" style="font-size: 0.76rem; color: #1C3C24;">
                                                        Track on carrier website <i class="bi bi-box-arrow-up-right ms-0.5"></i>
                                                    </a>
                                                @endif
                                            </div>

                                            @if ($trackingEvents->isNotEmpty())
                                                <div class="vr-timeline ps-2">
                                                    @foreach ($trackingEvents as $idx => $event)
                                                        @php
                                                            $isLatest = $idx === 0;
                                                        @endphp
                                                        <div class="d-flex gap-3 position-relative pb-3.5">
                                                            {{-- Connecting vertical line --}}
                                                            @if (! $loop->last)
                                                                <div class="position-absolute top-0 start-0 translate-middle-x"
                                                                     style="left: 11px; top: 12px; bottom: 0; width: 2px; background: #E5E7EB;"></div>
                                                            @endif

                                                            {{-- Checkpoint node dot --}}
                                                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 position-relative"
                                                                 style="width: 22px; height: 22px; z-index: 2; background: {{ $isLatest ? '#10B981' : '#D1D5DB' }}; color: #FFFFFF; font-size: 0.65rem; border: 3px solid #FFFFFF; box-shadow: 0 1px 4px rgba(0,0,0,0.1);">
                                                                @if ($isLatest)
                                                                    <i class="bi bi-check"></i>
                                                                @endif
                                                            </div>

                                                            <div class="flex-grow-1 min-w-0">
                                                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-0.5">
                                                                    <span class="fw-semibold text-dark" style="font-size: 0.85rem;">
                                                                        {{ ucwords(str_replace('_', ' ', $event->status)) }}
                                                                    </span>
                                                                    <span class="text-muted" style="font-size: 0.72rem;">
                                                                        {{ $event->event_date ? $event->event_date->format('d M, h:i A') : '' }}
                                                                    </span>
                                                                </div>

                                                                @if ($event->description)
                                                                    <div class="text-secondary small mb-1" style="font-size: 0.8rem; line-height: 1.4;">
                                                                        {{ $event->description }}
                                                                    </div>
                                                                @endif

                                                                @if ($event->location)
                                                                    <div class="d-inline-flex align-items-center gap-1 px-2 py-0.5 rounded text-muted" style="font-size: 0.72rem; background: #F3F4F6;">
                                                                        <i class="bi bi-geo-alt"></i> {{ $event->location }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif ($shipment)
                                                <div class="text-center py-4 px-2">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 text-muted" style="width: 44px; height: 44px; background: #F3F4F6;">
                                                        <i class="bi bi-upc-scan fs-5"></i>
                                                    </div>
                                                    <h6 class="fw-semibold text-dark small mb-1">Carrier Label Generated</h6>
                                                    <p class="text-muted small mb-0" style="font-size: 0.8rem;">
                                                        Your package has been assigned to <strong>{{ $courierName }}</strong> with AWB <strong>{{ $awb }}</strong>.
                                                        Checkpoints will be recorded here automatically once the courier scans the parcel during transit.
                                                    </p>
                                                </div>
                                            @else
                                                <div class="text-center py-4 px-2">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 text-muted" style="width: 44px; height: 44px; background: #F3F4F6;">
                                                        <i class="bi bi-box-seam fs-5"></i>
                                                    </div>
                                                    <h6 class="fw-semibold text-dark small mb-1">Order Under Preparation</h6>
                                                    <p class="text-muted small mb-0" style="font-size: 0.8rem;">
                                                        Our fulfillment team is preparing your items with luxury packaging. Live carrier tracking details will appear as soon as the package is ready for pickup.
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- RIGHT: Package Items & Delivery Address Card --}}
                                    <div class="col-12 col-lg-5">
                                        <div class="p-3.5 rounded-3 bg-white h-100 border" style="border-color: #F1F5F9 !important;">
                                            
                                            {{-- Items List --}}
                                            <h6 class="fw-bold mb-3 pb-2 border-bottom text-dark d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                                                <i class="bi bi-bag text-success"></i>
                                                <span>Items in Package ({{ $order->items->count() }})</span>
                                            </h6>
                                            <div class="d-flex flex-column gap-2.5 mb-3">
                                                @foreach ($order->items as $item)
                                                    <div class="d-flex align-items-center gap-2.5 p-2 rounded-2" style="background: #F9FAFB; border: 1px solid #F3F4F6;">
                                                        @if ($item->image)
                                                            <img src="{{ image_url($item->image) }}" alt="{{ $item->product_name }}"
                                                                 class="rounded-2 object-fit-cover flex-shrink-0" style="width: 44px; height: 44px;">
                                                        @else
                                                            <div class="rounded-2 d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 44px; height: 44px; background: #E5E7EB;">
                                                                <i class="bi bi-image"></i>
                                                            </div>
                                                        @endif
                                                        <div class="min-w-0 flex-grow-1">
                                                            <div class="fw-semibold text-dark text-truncate" style="font-size: 0.82rem;">
                                                                {{ $item->product_name }}
                                                            </div>
                                                            <div class="text-muted small" style="font-size: 0.72rem;">
                                                                Qty: {{ $item->quantity }} &middot; {{ format_price($item->unit_price) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            {{-- Delivery Destination --}}
                                            <h6 class="fw-bold mb-2 pt-2 border-top text-dark" style="font-size: 0.88rem;">
                                                <i class="bi bi-geo-alt me-1 text-muted"></i> Delivery Destination
                                            </h6>
                                            <div class="small text-muted mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                                                <div class="fw-semibold text-dark">{{ $order->shipping_name ?: $order->billing_name }}</div>
                                                <div>{{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}</div>
                                                <div>Payment: <span class="badge bg-light text-dark border">{{ strtoupper($order->payment_method ?: 'Prepaid') }}</span></div>
                                            </div>

                                            {{-- WhatsApp Concierge Help Button --}}
                                            @php
                                                $waPhone = preg_replace('/[^0-9]/', '', setting('store_whatsapp', '919876543210'));
                                                $waMsg = urlencode("Hi Vanriti, I need an update regarding my Order #{$order->order_number}.");
                                            @endphp
                                            <a href="https://wa.me/{{ $waPhone }}?text={{ $waMsg }}" target="_blank"
                                               class="btn w-100 btn-outline-success d-flex align-items-center justify-content-center gap-2 py-2"
                                               style="border-radius: 8px; font-size: 0.82rem;">
                                                <i class="bi bi-whatsapp"></i>
                                                <span>Need Help? Chat on WhatsApp</span>
                                            </a>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        @endif

    </div>
</div>

<style>
.vr-step-pulse {
    animation: vrPulse 2s infinite;
}
@keyframes vrPulse {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}
</style>

<script nonce="{{ $cspNonce }}">
function copyAwbToClipboard(awb) {
    if (!navigator.clipboard) {
        var temp = document.createElement('input');
        temp.value = awb;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
    } else {
        navigator.clipboard.writeText(awb);
    }
    var copyBtnText = document.getElementById('copyAwbText');
    if (copyBtnText) {
        copyBtnText.textContent = 'Copied!';
        setTimeout(function () {
            copyBtnText.textContent = 'Copy';
        }, 2000);
    }
    if (window.vrToast) {
        window.vrToast('AWB copied to clipboard: ' + awb);
    }
}
</script>
@endsection
