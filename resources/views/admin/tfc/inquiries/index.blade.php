@extends('admin.layouts.app')

@section('title', 'TFC Inquiries & Seller Applications')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-1">TFC Inquiries &amp; Seller Applications</h5>
        <p class="text-muted small mb-0">Review inbound prospective direct-seller registrations and corporate inquiries.</p>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.tfc.inquiries.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Inquiry Types</option>
                    <option value="seller_application" {{ request('type') === 'seller_application' ? 'selected' : '' }}>Prospective Direct Seller Application</option>
                    <option value="general" {{ request('type') === 'general' ? 'selected' : '' }}>General Corporate Inquiry</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                <a href="{{ route('admin.tfc.inquiries.index') }}" class="btn btn-light btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Applicant / Inquirer</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Date Received</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inquiries as $inquiry)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $inquiry->name }}</div>
                                <div class="text-muted small">
                                    <i class="bi bi-telephone me-1"></i>{{ $inquiry->phone }} |
                                    <i class="bi bi-envelope me-1"></i>{{ $inquiry->email }}
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $inquiry->type === 'seller_application' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ $inquiry->type === 'seller_application' ? 'Seller App' : 'General' }}
                                </span>
                            </td>
                            <td class="small">{{ $inquiry->city ?: '—' }}, {{ $inquiry->state ?: '—' }}</td>
                            <td class="small text-muted">{{ $inquiry->created_at->format('d M Y, h:i A') }}</td>
                            <td>
                                <span class="badge {{ $inquiry->status === 'new' ? 'bg-danger' : ($inquiry->status === 'in_progress' ? 'bg-warning text-dark' : 'bg-success') }}">
                                    {{ ucfirst(str_replace('_', ' ', $inquiry->status)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.tfc.inquiries.show', $inquiry) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <form action="{{ route('admin.tfc.inquiries.destroy', $inquiry) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No inquiries found matching criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($inquiries->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $inquiries->links() }}
        </div>
    @endif
</div>
@endsection
