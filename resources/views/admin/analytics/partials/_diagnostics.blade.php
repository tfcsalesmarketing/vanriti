@php
    $tracking = $report['tracking'] ?? [];
    $capi = $tracking['capi'] ?? [];
    $capiConfig = $capi['config'] ?? [];
    $capiStatusKey = $capi['status'] ?? 'unknown';
    $capiLabel = $capi['label'] ?? 'Unknown';
    $capiBadge = match ($capiStatusKey) {
        'active' => 'text-bg-success',
        'failed' => 'text-bg-danger',
        'configured_not_receiving' => 'text-bg-warning',
        'not_configured' => 'text-bg-secondary',
        default => 'text-bg-info',
    };
    $capiPixelConfigured = (bool) ($capiConfig['requirements']['pixel_present'] ?? false);
    $capiPixel = $capiPixelConfigured ? trim((string) setting('meta_pixel_id', '')) : '';
    $capiDelivery = match ($capiStatusKey) {
        'active' => 'Receiving',
        'failed' => 'Failing',
        'configured_not_receiving' => 'Not receiving',
        'not_configured' => 'Not delivering',
        default => 'Unproven',
    };
    $capiLastSent = $capi['last_sent_at'] ?? null;
@endphp

@if (($tracking['error'] ?? false))
    <div class="alert alert-warning py-2 small">{{ $tracking['message'] }}</div>
@else
    <div class="card table-card">
        <div class="card-header">Tracking &amp; Data Diagnostics</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th class="small">Check</th><th class="small">Status</th><th class="small">Detail</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="small">Currency</td>
                            <td><span class="badge text-bg-success">CONFIGURED</span></td>
                            <td class="small text-muted">{{ $tracking['currency'] }} ({{ currency() }})</td>
                        </tr>
                        <tr>
                            <td class="small">GA4 / GTM browser tracking</td>
                            <td>
                                @if ($tracking['gtm_configured'])
                                    <span class="badge text-bg-success">CONFIGURED</span>
                                @else
                                    <span class="badge text-bg-secondary">NOT CONFIGURED</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $tracking['gtm_configured'] ? 'GTM '.$tracking['gtm_container_id'] : 'No GTM container ID configured; GA4/GTM may be inactive.' }}</td>
                        </tr>
                        <tr>
                            <td class="small">Meta Pixel</td>
                            <td>
                                @if ($tracking['pixel_configured'])
                                    <span class="badge text-bg-success">CONFIGURED</span>
                                @else
                                    <span class="badge text-bg-danger">MISSING</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $tracking['pixel_configured'] ? 'Pixel ID '.$tracking['pixel_masked'] : 'Not set (Settings &rarr; SEO).' }}</td>
                        </tr>
                        <tr>
                            <td class="small">Meta Conversions API (CAPI)</td>
                            <td><span class="badge {{ $capiBadge }}">{{ strtoupper($capiLabel) }}</span></td>
                            <td class="small text-muted">
                                {{ $tracking['capi_status_detail'] ?: $capi['detail'] }}
                                <span class="d-block mt-2">
                                    <span class="d-block"><strong>Configuration:</strong>
                                        {{ ($capiConfig['configured'] ?? false) ? 'Configured' : 'Not configured' }}</span>
                                    <span class="d-block"><strong>Pixel:</strong>
                                        {{ $capiPixel !== '' ? $capiPixel : 'Not set' }}</span>
                                    <span class="d-block"><strong>Delivery:</strong>
                                        {{ $capiDelivery }}</span>
                                    <span class="d-block"><strong>Last successful delivery:</strong>
                                        {{ $capiLastSent ? \Carbon\Carbon::parse($capiLastSent)->toDayDateTimeString() : 'Never' }}</span>
                                    <span class="d-block"><strong>Recent delivered events (7 days):</strong>
                                        {{ (int) ($capi['delivered_7d'] ?? 0) }}</span>
                                    <span class="d-block"><strong>Failed deliveries (7 days):</strong>
                                        {{ (int) ($capi['failed_7d'] ?? 0) }}</span>
                                    <span class="d-block"><strong>Awaiting delivery:</strong>
                                        {{ (int) ($capi['awaiting_delivery'] ?? 0) }}</span>
                                </span>
                                <span class="d-block text-muted mt-1">Configuration alone never proves delivery - status is inferred from the conversion ledger. Browser Pixel activity is not treated as CAPI evidence.</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="small">Conversion ledger (internal record)</td>
                            <td><span class="badge text-bg-info">INFO</span></td>
                            <td class="small text-muted">
                                {{ $tracking['ledger_total'] }} purchase record(s) in period
                                @foreach ($tracking['ledger_by_state'] as $state => $cnt)
                                    &middot; {{ $state === 'null' ? 'null' : $state }}: {{ $cnt }}
                                @endforeach
                                &middot; failed (7 day): {{ $tracking['ledger_failures_7d'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="small">Payment Started (payments-ledger)</td>
                            <td><span class="badge text-bg-info">INFO</span></td>
                            <td class="small text-muted">{{ $tracking['payment_started'] }} distinct qualifying order(s) with payments activity in period. Internal business metric; not the browser <code>add_payment_info</code> event.</td>
                        </tr>
                        <tr>
                            <td class="small">Payment-info events (recorder)</td>
                            <td><span class="badge text-bg-info">INFO</span></td>
                            <td class="small text-muted">{{ $tracking['add_payment_info_events'] }} <code>add_payment_info</code> event(s) recorded via <code>analytics_events</code> in period (browser checkout intent; COD and Razorpay initiation).</td>
                        </tr>
                        <tr>
                            <td class="small">Event recorder health</td>
                            <td><span class="badge text-bg-info">INFO</span></td>
                            <td class="small text-muted">
                                {{ $tracking['recorder_total'] }} <code>analytics_events</code> row(s) in period
                                @foreach ($tracking['recorder_by_type'] as $type => $cnt)
                                    &middot; {{ $type }}: {{ $cnt }}
                                @endforeach
                                @if ($tracking['recorder_first_event'])
                                    &middot; first: {{ $tracking['recorder_first_event'] }}
                                @endif
                                @if ($tracking['recorder_last_event'])
                                    &middot; last: {{ $tracking['recorder_last_event'] }}
                                @endif
                                &middot; last server event: {{ $tracking['last_server_event'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="small">Visitor tracking</td>
                            <td>
                                @php
                                    $visitorStatus = $tracking['visitor_status'] ?? 'inactive';
                                    $visitorBadge = match ($visitorStatus) {
                                        'active' => 'text-bg-success',
                                        'limited' => 'text-bg-warning',
                                        default => 'text-bg-secondary',
                                    };
                                    $visitorLabel = match ($visitorStatus) {
                                        'active' => 'ACTIVE',
                                        'limited' => 'LIMITED',
                                        default => 'NOT ACTIVE',
                                    };
                                @endphp
                                <span class="badge {{ $visitorBadge }}">{{ $visitorLabel }}</span>
                            </td>
                            <td class="small text-muted">
                                {{ $tracking['visitor_events_with_id'] }} event(s) carry a visitor identity
                                &middot; {{ $tracking['visitor_events_without_id'] }} without one
                                &middot; {{ $tracking['visitor_unique_all_time'] }} unique visitor(s) all-time
                                &middot; {{ $tracking['visitor_sessions'] }} unique session(s) with a visitor-identified page view
                                @if ($tracking['visitor_first_event'])
                                    &middot; first: {{ $tracking['visitor_first_event'] }}
                                @endif
                                @if ($tracking['visitor_last_event'])
                                    &middot; last: {{ $tracking['visitor_last_event'] }}
                                @endif
                                <br>{{ $tracking['visitor_coverage_note'] }}
                            </td>
                        </tr>
                        <tr>
                            <td class="small">Tracking coverage (qualifying)</td>
                            <td>
                                @if (($tracking['tracked_orders'] ?? 0) + ($tracking['untracked_orders'] ?? 0) === 0)
                                    <span class="badge text-bg-secondary">N/A</span>
                                @elseif ($tracking['coverage_percent'] === 100)
                                    <span class="badge text-bg-success">FULL</span>
                                @else
                                    <span class="badge text-bg-warning">PARTIAL</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $tracking['tracked_orders'] }} tracked / {{ $tracking['untracked_orders'] }} untracked qualifying order(s) this period.</td>
                        </tr>
                        <tr>
                            <td class="small">Products missing SKU</td>
                            <td>
                                @if ($tracking['products_without_sku'] > 0)
                                    <span class="badge text-bg-warning">WARNING</span>
                                @else
                                    <span class="badge text-bg-success">OK</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $tracking['products_without_sku'] }} product(s) without a SKU.</td>
                        </tr>
                        <tr>
                            <td class="small">Duplicate SKUs</td>
                            <td>
                                @if ($tracking['duplicate_skus'] > 0)
                                    <span class="badge text-bg-danger">CRITICAL</span>
                                @else
                                    <span class="badge text-bg-success">OK</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $tracking['duplicate_skus'] }} duplicated SKU(s).</td>
                        </tr>
                        <tr>
                            <td class="small">Order items missing SKU (period)</td>
                            <td>
                                @if ($tracking['orders_missing_item_sku'] > 0)
                                    <span class="badge text-bg-warning">WARNING</span>
                                @else
                                    <span class="badge text-bg-success">OK</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $tracking['orders_missing_item_sku'] }} qualifying item line(s) without a SKU.</td>
                        </tr>
                        <tr>
                            <td class="small">Active stock health</td>
                            <td>
                                @if ($tracking['active_out_of_stock'] > 0)
                                    <span class="badge text-bg-warning">WARNING</span>
                                @else
                                    <span class="badge text-bg-success">OK</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $tracking['active_out_of_stock'] }} out of stock, {{ $tracking['active_low_stock'] }} low on stock (active products).</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif