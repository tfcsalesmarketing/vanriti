@extends('admin.layouts.app')

@section('title', 'TFC Direct Sellers Directory')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
            <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; background: #0A1D37;">
                <i class="bi bi-diagram-3-fill fs-6" style="color: #D49B24;"></i>
            </span>
            <span>TFC Direct Sellers &amp; Customer Accounts</span>
        </h5>
        <div class="text-muted small mt-0.5">Independent direct sellers, customer profiles, storefront order history, and direct WhatsApp engagements</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1.5 shadow-sm" title="Switch to VANRITI Storefront Customers">
            <i class="bi bi-shop"></i><span>VANRITI Retail Customers ({{ number_format($kpis['vanriti_customers_count'] ?? 0) }}) &rarr;</span>
        </a>
        <a href="{{ route('admin.tfc.members.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 shadow-sm" title="View Genealogy Member Directory">
            <i class="bi bi-diagram-3"></i><span>Genealogy Tree</span>
        </a>
    </div>
</div>

{{-- Top 4 KPI Summary Ribbon --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #0A1D37 0%, #132D54 100%); color: #fff;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem; color: #CBD5E1;">Total Direct Sellers</span>
                    <span class="badge rounded-pill p-2" style="background: rgba(212, 155, 36, 0.2); color: #D49B24;"><i class="bi bi-diagram-3-fill fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1" style="color: #F8FAFC;">{{ number_format($kpis['total']) }}</div>
                <div class="small" style="font-size:0.75rem; color: #94A3B8;">Registered direct selling accounts</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">Active Membership</span>
                    <span class="badge bg-success-subtle text-success rounded-pill p-2"><i class="bi bi-check-circle-fill fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-success">{{ number_format($kpis['active_members']) }}</div>
                <div class="small text-muted" style="font-size:0.75rem;">In good standing</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">Ordering Sellers</span>
                    <span class="badge bg-info-subtle text-info rounded-pill p-2"><i class="bi bi-bag-check-fill fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-primary">{{ number_format($kpis['ordering_sellers']) }}</div>
                <div class="small text-muted" style="font-size:0.75rem;">Placed &ge; 1 order</div>
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
                <div class="small text-muted" style="font-size:0.75rem;">Mobile numbers connected</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Toolbar --}}
<div class="card filter-card mb-3 shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.tfc.customers.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Search name, phone, email, Member ID..." value="{{ request('q') }}">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="leg" class="form-select form-select-sm">
                    <option value="">All Placement Legs</option>
                    <option value="orange" {{ request('leg') === 'orange' ? 'selected' : '' }}>🟠 Orange Leg</option>
                    <option value="blue" {{ request('leg') === 'blue' ? 'selected' : '' }}>🔵 Blue Leg</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="kyc" class="form-select form-select-sm">
                    <option value="">All KYC Status</option>
                    <option value="approved" {{ request('kyc') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="pending" {{ request('kyc') === 'pending' ? 'selected' : '' }}>Pending Review</option>
                    <option value="rejected" {{ request('kyc') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="not_submitted" {{ request('kyc') === 'not_submitted' ? 'selected' : '' }}>Not Submitted</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="sort" class="form-select form-select-sm">
                    <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Newest Joined</option>
                    <option value="clv_desc" {{ request('sort') === 'clv_desc' ? 'selected' : '' }}>Highest Spend (CLV)</option>
                    <option value="orders_desc" {{ request('sort') === 'orders_desc' ? 'selected' : '' }}>Most Orders</option>
                    <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name (A-Z)</option>
                </select>
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Apply Filters"><i class="bi bi-funnel"></i></button>
                <a href="{{ route('admin.tfc.customers.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- Sellers List Table --}}
<div class="card border-0 shadow-sm" style="border-radius: 12px;">
    <div class="card-body p-0">
        @if ($sellers->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Direct Seller</th>
                            <th>Contact Info</th>
                            <th>Network Placement</th>
                            <th>KYC Status</th>
                            <th>Store Orders</th>
                            <th>Total Spent (CLV)</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @foreach ($sellers as $seller)
                            @php
                                $member = $seller->directSellingMember;
                                $kyc = $member?->kyc;
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar-circle" style="background: #0A1D37; color: #D49B24; width: 34px; height: 34px; font-size: 0.8rem;">
                                            {{ strtoupper(substr($seller->name, 0, 1)) }}
                                        </span>
                                        <div>
                                            <a href="{{ route('admin.tfc.customers.show', $seller->id) }}" class="fw-semibold text-dark text-decoration-none hover-primary">
                                                {{ $seller->name }}
                                            </a>
                                            <div class="font-monospace text-primary fw-bold" style="font-size: 0.72rem;">
                                                {{ $member?->member_id ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        @if ($seller->phone)
                                            <span class="fw-medium font-monospace">{{ $seller->phone }}</span>
                                            @if ($seller->whatsappConversation)
                                                <i class="bi bi-whatsapp text-success ms-1" title="WhatsApp Connected"></i>
                                            @endif
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small">{{ $seller->email ?: 'No email' }}</div>
                                </td>
                                <td>
                                    <div>
                                        @if ($member?->sponsor)
                                            <span class="text-dark">{{ $member->sponsor->full_name }}</span>
                                            <span class="text-muted font-monospace small">({{ $member->sponsor->member_id }})</span>
                                        @else
                                            <span class="badge text-bg-light border text-muted">Direct</span>
                                        @endif
                                    </div>
                                    <div class="mt-0.5">
                                        @if ($member?->placement_position === 'orange')
                                            <span class="badge" style="background: rgba(249, 115, 22, 0.12); color: #C2410C;">🟠 Orange</span>
                                        @elseif ($member?->placement_position === 'blue')
                                            <span class="badge" style="background: rgba(59, 130, 246, 0.12); color: #1D4ED8;">🔵 Blue</span>
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($kyc?->status === 'approved')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Approved</span>
                                    @elseif ($kyc?->status === 'pending')
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-clock me-1"></i>Pending</span>
                                    @elseif ($kyc?->status === 'rejected')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Not Submitted</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $seller->orders_count }}</span>
                                    <span class="text-muted small">orders</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ format_price($seller->total_spent ?? 0) }}</div>
                                </td>
                                <td>
                                    @php
                                        $memStatus = $member?->membership_status ?? 'active';
                                    @endphp
                                    <span class="badge {{ $memStatus === 'active' ? 'bg-success' : ($memStatus === 'suspended' ? 'bg-danger' : 'bg-secondary') }}">
                                        {{ ucfirst($memStatus) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $seller->created_at->format('d M Y') }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        @if ($seller->phone)
                                            <a href="{{ route('admin.whatsapp.inbox', ['user_id' => $seller->id]) }}" class="btn btn-xs btn-outline-success py-1 px-2" title="Chat on WhatsApp" style="font-size: 0.72rem;">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.tfc.customers.show', $seller->id) }}" class="btn btn-xs btn-outline-primary py-1 px-2" title="Customer 360 Profile" style="font-size: 0.72rem;">
                                            <i class="bi bi-person"></i> Profile
                                        </a>
                                        @if ($member)
                                            <a href="{{ route('admin.tfc.members.show', $member->id) }}" class="btn btn-xs text-white py-1 px-2" style="background: #0A1D37; font-size: 0.72rem;" title="View Genealogy / Member Profile">
                                                <i class="bi bi-diagram-3" style="color: #D49B24;"></i> Member
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($sellers->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Showing {{ $sellers->firstItem() }} to {{ $sellers->lastItem() }} of {{ $sellers->total() }} direct sellers
                    </div>
                    <div>{{ $sellers->links() }}</div>
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 56px; height: 56px; background: rgba(10, 29, 55, 0.06);">
                    <i class="bi bi-people fs-3" style="color: #0A1D37;"></i>
                </div>
                <h6 class="fw-bold mb-1">No direct sellers found</h6>
                <p class="text-muted small mb-3">No direct selling member accounts match the specified filters.</p>
                <a href="{{ route('admin.tfc.customers.index') }}" class="btn btn-sm btn-outline-secondary">Reset Filters</a>
            </div>
        @endif
    </div>
</div>
@endsection
