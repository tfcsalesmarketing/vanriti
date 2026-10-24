@extends('admin.layouts.app')

@section('title', 'Customer Directory & Segment Analytics')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Customer 360° Directory</h5>
        <div class="text-muted small mt-0.5">Customer profiles, segmentation, lifetime values, and direct WhatsApp engagements</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.whatsapp.inbox') }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1.5 shadow-sm">
            <i class="bi bi-whatsapp"></i><span>WhatsApp Live Inbox</span>
        </a>
    </div>
</div>

{{-- Top 4 KPI Summary Ribbon --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">Total Customers</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill p-2"><i class="bi bi-people-fill fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-dark">{{ number_format($kpis['total']) }}</div>
                <div class="small text-muted" style="font-size:0.75rem;">Registered user accounts</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">Active Shoppers</span>
                    <span class="badge bg-success-subtle text-success rounded-pill p-2"><i class="bi bi-bag-check-fill fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-success">{{ number_format($kpis['active_shoppers']) }}</div>
                <div class="small text-muted" style="font-size:0.75rem;">Have placed &ge; 1 confirmed order</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">VIP Champions</span>
                    <span class="badge bg-info-subtle text-info rounded-pill p-2"><i class="bi bi-stars fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-primary">{{ number_format($kpis['vip']) }}</div>
                <div class="small text-muted" style="font-size:0.75rem;">CLV &ge; ₹2,500 or 3+ orders</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">WhatsApp Reachable</span>
                    <span class="badge bg-success-subtle text-success rounded-pill p-2"><i class="bi bi-whatsapp fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-dark">{{ number_format($kpis['whatsapp_connected']) }}</div>
                <div class="small text-muted" style="font-size:0.75rem;">Mobile numbers available</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Toolbar & Segment Tabs --}}
<div class="card filter-card mb-3 shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-3">
        {{-- Segment Filter Tabs --}}
        <div class="d-flex flex-wrap gap-2 mb-3 pb-2 border-bottom">
            @php $currentSegment = request('segment', 'all'); @endphp
            <a href="{{ route('admin.customers.index', array_merge(request()->except('segment', 'page'), ['segment' => 'all'])) }}"
               class="btn btn-sm rounded-pill px-3 py-1.5 {{ $currentSegment === 'all' ? 'btn-dark' : 'btn-light border text-muted' }}">
                All Customers
            </a>
            <a href="{{ route('admin.customers.index', array_merge(request()->except('segment', 'page'), ['segment' => 'vip'])) }}"
               class="btn btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 {{ $currentSegment === 'vip' ? 'btn-primary' : 'btn-light border text-muted' }}">
                <i class="bi bi-stars text-warning"></i><span>VIP Champions</span>
            </a>
            <a href="{{ route('admin.customers.index', array_merge(request()->except('segment', 'page'), ['segment' => 'repeat'])) }}"
               class="btn btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 {{ $currentSegment === 'repeat' ? 'btn-info text-white' : 'btn-light border text-muted' }}">
                <i class="bi bi-arrow-repeat text-info"></i><span>Repeat Buyers</span>
            </a>
            <a href="{{ route('admin.customers.index', array_merge(request()->except('segment', 'page'), ['segment' => 'first_time'])) }}"
               class="btn btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 {{ $currentSegment === 'first_time' ? 'btn-success text-white' : 'btn-light border text-muted' }}">
                <i class="bi bi-bag-check text-success"></i><span>First-Time Buyers</span>
            </a>
            <a href="{{ route('admin.customers.index', array_merge(request()->except('segment', 'page'), ['segment' => 'prospect'])) }}"
               class="btn btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 {{ $currentSegment === 'prospect' ? 'btn-secondary text-white' : 'btn-light border text-muted' }}">
                <i class="bi bi-person text-secondary"></i><span>Prospects &amp; Leads</span>
            </a>
        </div>

        <form method="GET" action="{{ route('admin.customers.index') }}" class="row g-2 align-items-center">
            @if (request('segment') && request('segment') !== 'all')
                <input type="hidden" name="segment" value="{{ request('segment') }}">
            @endif

            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Search customer by name, email, or mobile..." value="{{ request('q') }}">
                </div>
            </div>

            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Status: All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Sort: Newest First</option>
                    <option value="clv_desc" {{ request('sort') === 'clv_desc' ? 'selected' : '' }}>Sort: Highest CLV</option>
                    <option value="orders_desc" {{ request('sort') === 'orders_desc' ? 'selected' : '' }}>Sort: Most Orders</option>
                    <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Sort: Name (A-Z)</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Sort: Oldest First</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <div class="form-check form-check-inline small pt-1">
                    <input class="form-check-input" type="checkbox" name="has_whatsapp" id="hasWhatsApp" value="1" {{ request('has_whatsapp') ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="form-check-label text-muted" for="hasWhatsApp"><i class="bi bi-whatsapp text-success me-1"></i>Has Phone</label>
                </div>
            </div>

            <div class="col-6 col-md-2 ms-auto d-flex gap-1 justify-content-end">
                <button type="submit" class="btn btn-sm btn-dark flex-fill" title="Filter"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- Customers Table --}}
<div class="card table-card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-3">Customer</th>
                    <th>WhatsApp &amp; Contact</th>
                    <th>Customer Segment</th>
                    <th class="text-center">Orders</th>
                    <th class="text-end">Lifetime Value (CLV)</th>
                    <th>Last Active</th>
                    <th class="text-center">Status</th>
                    <th class="text-end pe-3" style="min-width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    @php
                        $segment = $customer->getCustomerSegment();
                        $cleanPhone = $customer->getCleanPhone();
                        $lastActive = $customer->getLastActiveAt();
                    @endphp
                    <tr>
                        {{-- Customer Profile --}}
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2.5">
                                <span class="avatar-circle shadow-sm fw-bold {{ $segment['key'] === 'vip' ? 'bg-primary text-white' : ($customer->status === 'active' ? 'bg-light text-dark border' : 'bg-secondary text-white') }}"
                                      style="width: 38px; height: 38px; min-width: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </span>
                                <div>
                                    <div class="fw-bold small">
                                        <a href="{{ route('admin.customers.show', $customer->id) }}" class="text-decoration-none text-dark hover-green">
                                            {{ $customer->name }}
                                        </a>
                                    </div>
                                    <div class="text-muted small" style="font-size:0.75rem;">{{ $customer->email }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- WhatsApp & Contact --}}
                        <td class="small">
                            @if ($cleanPhone)
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-dark font-monospace" style="font-size:0.8rem;">+{{ $cleanPhone }}</span>
                                    <a href="{{ route('admin.whatsapp.inbox', ['user_id' => $customer->id]) }}"
                                       class="btn btn-xs btn-outline-success p-0 px-1.5 rounded-pill d-inline-flex align-items-center gap-1 shadow-sm"
                                       title="Open Live Chat in WhatsApp Inbox"
                                       style="font-size:0.7rem; line-height: 1.5;">
                                        <i class="bi bi-whatsapp"></i><span>Chat</span>
                                    </a>
                                </div>
                                @if ($customer->phone_verified_at)
                                    <small class="text-success" style="font-size:0.68rem;"><i class="bi bi-check-circle-fill me-0.5"></i>Verified</small>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>

                        {{-- Customer Segment --}}
                        <td>
                            <span class="badge bg-{{ $segment['badge'] }}-subtle text-{{ $segment['badge'] }} border border-{{ $segment['badge'] }}-subtle px-2 py-1 rounded-pill small" title="{{ $segment['description'] }}">
                                <i class="bi {{ $segment['icon'] }} me-1"></i>{{ $segment['label'] }}
                            </span>
                        </td>

                        {{-- Orders Count --}}
                        <td class="text-center small">
                            @if ($customer->orders_count > 0)
                                <span class="badge bg-light text-dark border px-2.5 py-1 fw-semibold">{{ $customer->orders_count }}</span>
                            @else
                                <span class="text-muted small">0</span>
                            @endif
                        </td>

                        {{-- Lifetime Value (CLV) --}}
                        <td class="text-end small">
                            <span class="fw-bold text-dark fs-6">{{ format_price($customer->total_spent ?? 0) }}</span>
                        </td>

                        {{-- Last Active --}}
                        <td class="small text-muted" style="white-space: nowrap;">
                            @if ($lastActive)
                                <div title="{{ $lastActive->format('d M Y, h:i A') }}">{{ $lastActive->diffForHumans() }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- 1-Click Status Pill --}}
                        <td class="text-center">
                            <button type="button"
                                    class="badge border-0 cursor-pointer js-customer-status-btn px-2.5 py-1 rounded-pill small {{ $customer->status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}"
                                    data-url="{{ route('admin.customers.toggle', $customer->id) }}"
                                    data-id="{{ $customer->id }}"
                                    title="Click to toggle active/inactive status">
                                <span class="status-text">{{ ucfirst($customer->status) }}</span>
                            </button>
                        </td>

                        {{-- Actions --}}
                        <td class="text-end pe-3">
                            <div class="d-flex gap-1 justify-content-end align-items-center">
                                @if ($cleanPhone)
                                    <a href="{{ route('admin.whatsapp.inbox', ['user_id' => $customer->id]) }}"
                                       class="btn btn-sm btn-outline-success p-1 px-2"
                                       title="Live WhatsApp Chat">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                @endif
                                <a href="{{ route('admin.customers.show', $customer->id) }}"
                                   class="btn btn-sm btn-outline-primary p-1 px-2"
                                   title="View Customer 360° Profile &amp; Purchase History">
                                    <i class="bi bi-person-lines-fill"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state py-5 text-center">
                                <div class="empty-state-icon fs-1 text-muted mb-2"><i class="bi bi-people"></i></div>
                                <div class="empty-state-title fw-bold">No Customers Found</div>
                                <p class="empty-state-desc text-muted small">No customer profiles match your current search and segment filter criteria.</p>
                                <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Clear Filters
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($customers->hasPages())
        <div class="card-footer bg-white d-flex align-items-center justify-content-between p-3 border-top">
            <span class="small text-muted">Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ $customers->total() }} customers</span>
            <div>{{ $customers->links() }}</div>
        </div>
    @endif
</div>

{{-- Inline JavaScript for 1-Click Status Toggle --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    document.querySelectorAll('.js-customer-status-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const btn = this;
            const url = btn.dataset.url;
            const originalHtml = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    if (data.status === 'active') {
                        btn.className = 'badge border-0 cursor-pointer js-customer-status-btn px-2.5 py-1 rounded-pill small bg-success-subtle text-success';
                        btn.innerHTML = '<span class="status-text">Active</span>';
                    } else {
                        btn.className = 'badge border-0 cursor-pointer js-customer-status-btn px-2.5 py-1 rounded-pill small bg-danger-subtle text-danger';
                        btn.innerHTML = '<span class="status-text">Inactive</span>';
                    }
                } else {
                    btn.innerHTML = originalHtml;
                    alert(data.error || 'Failed to update customer status.');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                console.error(err);
                alert('An error occurred while updating status.');
            });
        });
    });
});
</script>
@endpush
@endsection
