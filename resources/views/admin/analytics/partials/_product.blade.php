@php
    $eventReport = $report['products']['event_report'] ?? [];
    $orderReport = $report['products']['report'] ?? [];
    $eventItems = $eventReport['items'] ?? [];
    $queryBase = request()->query();
    $pageUrl = function (int $page) use ($queryBase) {
        return route('admin.analytics', array_merge($queryBase, ['page' => max(1, $page)]));
    };
    $paginator = function (array $r) use ($pageUrl) {
        $current = (int) ($r['current_page'] ?? 1);
        $last = (int) ($r['last_page'] ?? 1);
        if ($last <= 1) {
            return '';
        }
        $prev = $pageUrl($current - 1);
        $next = $pageUrl($current + 1);
        return '<nav aria-label="Pagination"><ul class="pagination pagination-sm mb-0 justify-content-end">'
            .'<li class="page-item '.($current <= 1 ? 'disabled' : '').'"><a class="page-link" href="'.$prev.'">Prev</a></li>'
            .'<li class="page-item disabled"><span class="page-link">Page '.$current.' of '.$last.'</span></li>'
            .'<li class="page-item '.($current >= $last ? 'disabled' : '').'"><a class="page-link" href="'.$next.'">Next</a></li>'
            .'</ul></nav>';
    };
@endphp

<div class="card mb-4">
    <div class="card-header">
        <h6 class="m-0 fw-semibold">Product Performance</h6>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Merges order snapshots (Purchases / Units Sold / Revenue - Laravel DB authoritative) with storefront events by SKU
            (Views = <code>view_item</code>, Add to Cart = <code>add_to_cart</code>, Checkout = <code>begin_checkout</code>).
            SKUs without a matching product name in orders are labelled <code>(unmatched)</code>. Conversion Rate = Purchases ÷ Views.
            Events are unavailable until sessions are recorded in <code>analytics_events</code>.
        </p>

        <form method="GET" action="{{ route('admin.analytics') }}" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="section" value="product">
            <input type="hidden" name="preset" value="{{ $preset }}">
            <input type="hidden" name="from" value="{{ request('from', $from) }}">
            <input type="hidden" name="to" value="{{ request('to', $to) }}">
            <input type="hidden" name="page" value="1">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1" for="productFilter">Product name contains</label>
                <input type="text" name="product" id="productFilter" class="form-control form-control-sm"
                       value="{{ request('product', '') }}" placeholder="e.g. snapback">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1" for="categoryFilter">Category</label>
                <select name="category_id" id="categoryFilter" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <button type="submit" class="btn btn-sm btn-primary">Apply Filter</button>
                @if (request('product') || request('category_id'))
                    <a href="{{ route('admin.analytics', array_merge($queryBase, ['product' => '', 'category_id' => '', 'page' => 1, 'section' => 'product'])) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>

        @if (empty($eventItems) && (request('product') || request('category_id')))
            <div class="empty-state p-4">
                <div class="empty-state-icon"><i class="bi bi-search"></i></div>
                <p class="fw-medium mb-1">No products match your filter</p>
                <p class="text-muted mb-0 small">Try a different product name or category.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-2">
                    <thead>
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">SKU</th>
                            <th scope="col" class="text-end">Views</th>
                            <th scope="col" class="text-end">Add to Cart</th>
                            <th scope="col" class="text-end">Checkout</th>
                            <th scope="col" class="text-end">Purchases</th>
                            <th scope="col" class="text-end">Units Sold</th>
                            <th scope="col" class="text-end">Revenue</th>
                            <th scope="col" class="text-end">Conv. Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($eventItems as $row)
                            <tr>
                                <td>{{ $row['product_name'] }}</td>
                                <td><code>{{ $row['sku'] }}</code></td>
                                <td class="text-end">{{ number_format((int) ($row['views'] ?? 0)) }}</td>
                                <td class="text-end">{{ number_format((int) ($row['add_to_carts'] ?? 0)) }}</td>
                                <td class="text-end">{{ number_format((int) ($row['checkouts'] ?? 0)) }}</td>
                                <td class="text-end">{{ number_format((int) ($row['purchases'] ?? 0)) }}</td>
                                <td class="text-end">{{ number_format((int) ($row['units'] ?? 0)) }}</td>
                                <td class="text-end fw-semibold">{{ format_price((float) ($row['revenue'] ?? 0)) }}</td>
                                <td class="text-end">{{ ($row['conversion_rate'] ?? null) === null ? '—' : number_format((float) $row['conversion_rate'], 1).'%' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox me-1"></i>No product rows yet. Events and orders will appear here once recording begins.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">
                <div class="text-muted small me-3 pt-1">{{ $eventReport['total'] ?? 0 }} product(s)</div>
                {!! $paginator($eventReport) !!}
            </div>
        @endif
    </div>
</div>

<div class="card table-card">
    <div class="card-header">
        <h6 class="m-0 fw-semibold">Order-Side Product Report <span class="text-muted small fw-normal">(order item snapshots, no event data)</span></h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Product</th>
                        <th scope="col">SKU</th>
                        <th scope="col" class="text-end">Units</th>
                        <th scope="col" class="text-end">Orders</th>
                        <th scope="col" class="text-end">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($orderReport['items'] ?? []) as $row)
                        <tr>
                            <td>{{ $row['product_name'] }}</td>
                            <td><code>{{ $row['sku'] }}</code></td>
                            <td class="text-end">{{ $row['units'] }}</td>
                            <td class="text-end">{{ $row['orders'] }}</td>
                            <td class="text-end fw-semibold">{{ format_price((float) $row['revenue']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i>No qualifying orders in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (($orderReport['last_page'] ?? 1) > 1)
            <div class="border-top p-2 d-flex justify-content-end">
                {!! $paginator($orderReport) !!}
            </div>
        @endif
    </div>
</div>