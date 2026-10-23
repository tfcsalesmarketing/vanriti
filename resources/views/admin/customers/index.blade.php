@extends('admin.layouts.app')

@section('title', 'Customers')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Customer Directory</h5>
        <div class="text-muted small mt-0.5">Profiles, purchase histories, and lifetime values across {{ $customers->total() }} customer(s)</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-dark border px-3 py-1.5 fw-semibold">
            <i class="bi bi-people me-1 text-primary"></i>{{ $customers->total() }} Registered
        </span>
    </div>
</div>

<div class="card filter-card mb-3">
    <form method="GET" action="{{ route('admin.customers.index') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="Search customer by name, email, or mobile..." value="{{ request('q') }}">
            </div>
        </div>
        <div class="col-12 col-md-2 ms-auto d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-dark flex-fill" title="Search"><i class="bi bi-search me-1"></i>Search</button>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear Search"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Mobile Phone</th>
                    <th>Account Status</th>
                    <th class="text-center">Orders</th>
                    <th class="text-end">Lifetime Value</th>
                    <th>Member Since</th>
                    <th class="text-end" style="min-width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                <span class="avatar-circle">
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
                        <td class="small">
                            @if ($customer->phone)
                                <span class="text-dark">{{ $customer->phone }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-soft-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($customer->status) }}
                            </span>
                        </td>
                        <td class="text-center small">
                            <span class="badge bg-light text-dark border">{{ $customer->orders_count }}</span>
                        </td>
                        <td class="text-end fw-bold small text-dark">{{ format_price($customer->total_spent ?? 0) }}</td>
                        <td class="small text-muted" style="white-space: nowrap;">{{ $customer->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end align-items-center">
                                <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="View Profile & Orders">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.customers.toggle', $customer->id) }}" data-confirm="{{ $customer->status === 'active' ? 'Deactivate' : 'Activate' }} this customer?">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $customer->status === 'active' ? 'warning' : 'success' }} p-1 px-2" title="{{ $customer->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi {{ $customer->status === 'active' ? 'bi-person-x' : 'bi-person-check' }}"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state py-5">
                                <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                                <div class="empty-state-title">No Customers Found</div>
                                <p class="empty-state-desc">No customer profiles match your search criteria.</p>
                                <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Clear Search
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($customers->hasPages())
        <div class="card-footer d-flex align-items-center justify-content-between">
            <span class="small text-muted">Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ $customers->total() }} customers</span>
            <div>{{ $customers->links() }}</div>
        </div>
    @endif
</div>
@endsection
