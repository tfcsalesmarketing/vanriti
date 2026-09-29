@php
    $events = $report['events'] ?? [];
    $purchases = (int) ($report['executive']['orders'] ?? 0);
    $visitors = (int) ($events['visitors'] ?? 0);
    $stages = [
        ['name' => 'Website Visitors / Unique Sessions', 'count' => $visitors, 'hint' => 'Distinct hashed session IDs (page_view).'],
        ['name' => 'Product Views', 'count' => (int) ($events['product_views'] ?? 0), 'hint' => 'view_item events on product pages.'],
        ['name' => 'Add to Cart', 'count' => (int) ($events['add_to_carts'] ?? 0), 'hint' => 'add_to_cart events after successful cart additions.'],
        ['name' => 'Initiate Checkout', 'count' => (int) ($events['begin_checkouts'] ?? 0), 'hint' => 'begin_checkout events when checkout is rendered.'],
        ['name' => 'Payment Started', 'count' => (int) ($report['payment_started']['count'] ?? 0), 'hint' => 'Distinct qualifying orders with a payments-ledger record (internal business metric).'],
        ['name' => 'Purchase', 'count' => $purchases, 'hint' => 'Qualifying orders created in the period (Laravel DB authoritative).'],
    ];
    $maxCount = max($visitors, 1);
@endphp

<div class="card table-card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h6 class="m-0 fw-semibold">Storefront Funnel</h6>
        <button class="btn btn-sm btn-outline-secondary" type="button"
                data-bs-toggle="collapse" data-bs-target="#funnelDisclaimer" aria-expanded="false"
                aria-controls="funnelDisclaimer">
            How to read this
        </button>
    </div>
    <div class="collapse" id="funnelDisclaimer">
        <div class="px-3 pt-3 small text-muted border-bottom">
            This is an <strong>aggregate event funnel</strong> - each stage counts events recorded for that stage in the
            period. One session may contribute to several stages, and stage counts are not a same-user journey. Treat the
            stage-to-stage rates as directional, not precise. Payment Started reflects your internal payments ledger
            (a business metric) and is intentionally not equated with the browser <code>add_payment_info</code> event.
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Stage</th>
                        <th scope="col" class="text-end">Count</th>
                        <th scope="col" class="text-end" style="min-width:130px;">% of Visitors</th>
                        <th scope="col" style="min-width:160px;">Funnel</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stages as $i => $stage)
                        @php
                            $rate = $visitors > 0 ? ($stage['count'] / $visitors) * 100 : null;
                            $bar = ($stage['count'] / $maxCount) * 100;
                            $tone = $i === count($stages) - 1 ? 'success' : 'primary';
                        @endphp
                        <tr title="{{ $stage['hint'] }}">
                            <td class="fw-medium">{{ $stage['name'] }}</td>
                            <td class="text-end fw-bold">{{ number_format($stage['count']) }}</td>
                            <td class="text-end text-muted">{{ $rate === null ? '—' : number_format($rate, 1).'%' }}</td>
                            <td>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-{{ $tone }}" style="width: {{ $bar }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>