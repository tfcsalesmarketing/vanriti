@extends('admin.layouts.app')

@section('title', 'Reviews')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Reviews</h5>
        <small class="text-muted">{{ $reviews->total() }} reviews total</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search product, title or comment..." value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="hidden" {{ request('status') === 'hidden' ? 'selected' : '' }}>Hidden</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-dark"><i class="bi bi-search me-1"></i>Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.reviews.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $statusColors = [
                        'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'hidden' => 'secondary',
                    ];
                @endphp
                @forelse ($reviews as $review)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @php $img = $review->product?->images->first(); @endphp
                                @if ($img)
                                    <img src="{{ image_url($img->image_path) }}" alt="" class="rounded" width="40" height="40" style="object-fit:cover;">
                                @else
                                    <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                                <div class="small fw-semibold" style="max-width:180px;">
                                    <span class="text-truncate d-block">{{ $review->product?->name ?: 'Product #' . $review->product_id }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $review->user?->name ?: '—' }}</td>
                        <td>
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= $review->rating ? 'bi-star-fill text-warning' : 'bi-star text-muted' }}" style="font-size:0.7rem;"></i>
                            @endfor
                        </td>
                        <td>
                            <div class="small fw-semibold">{{ $review->title ?: '—' }}</div>
                            @if ($review->comment)
                                <div class="text-muted" style="font-size:0.75rem;">{{ Str::limit($review->comment, 80) }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $statusColors[$review->status] ?? 'secondary' }}">{{ ucfirst($review->status) }}</span>
                        </td>
                        <td class="small text-muted">{{ $review->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                @if ($review->status !== 'approved')
                                    <form method="POST" action="{{ route('admin.reviews.approve', $review->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Approve"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                @endif
                                @if ($review->status !== 'rejected')
                                    <form method="POST" action="{{ route('admin.reviews.reject', $review->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Reject"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.reviews.destroy', $review->id) }}" onsubmit="return confirm('Delete this review?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No reviews found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $reviews->links() }}
    </div>
</div>
@endsection
