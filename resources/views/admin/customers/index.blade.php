@extends('admin.layouts.app')

@section('title', 'Customers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Customers</h5>
        <small class="text-muted">{{ $customers->total() }} customers total</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name, email or phone..." value="{{ request('q') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-dark"><i class="bi bi-search me-1"></i>Search</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th class="text-center">Orders</th>
                    <th class="text-end">Total Spent</th>
                    <th>Joined</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-circle bg-primary text-white">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                <div>
                                    <div class="fw-semibold small">
                                        <a href="{{ route('admin.customers.show', $customer->id) }}" class="text-decoration-none text-dark">{{ $customer->name }}</a>
                                    </div>
                                    <div class="text-muted" style="font-size:0.75rem;">{{ $customer->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $customer->phone ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $customer->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($customer->status) }}</span>
                        </td>
                        <td class="text-center">{{ $customer->orders_count }}</td>
                        <td class="text-end fw-semibold small">{{ format_price($customer->total_spent ?? 0) }}</td>
                        <td class="small text-muted">{{ $customer->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                <form method="POST" action="{{ route('admin.customers.toggle', $customer->id) }}" onsubmit="return confirm('{{ $customer->status === 'active' ? 'Deactivate' : 'Activate' }} this customer?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $customer->status === 'active' ? 'warning' : 'success' }}" title="{{ $customer->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi {{ $customer->status === 'active' ? 'bi-person-x' : 'bi-person-check' }}"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $customers->links() }}
    </div>
</div>
@endsection
