@extends('admin.layouts.app')

@section('title', 'Reviews')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Reviews</h5>
        <small class="text-muted">{{ $reviews->total() }} reviews total</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-xs" data-bs-toggle="modal" data-bs-target="#importReviewsModal">
            <i class="bi bi-upload"></i><span>Import Reviews CSV</span>
        </button>
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
                        <td class="small">
                            <div class="fw-semibold">{{ $review->reviewer_name }}</div>
                            @if (!$review->user_id)
                                <span class="badge bg-light text-muted border" style="font-size:0.65rem;">Direct / Guest</span>
                            @endif
                        </td>
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
                            @if ($review->images->isNotEmpty())
                                <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                    @foreach ($review->images->take(4) as $image)
                                        <img src="{{ image_url($image->path) }}" alt=""
                                             width="28" height="28" class="rounded"
                                             style="object-fit:cover;border:1px solid var(--vr-border);">
                                    @endforeach
                                    @if ($review->images->count() > 4)
                                        <span class="text-muted" style="font-size:0.7rem;">+{{ $review->images->count() - 4 }} more</span>
                                    @endif
                                </div>
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
                                <form method="POST" action="{{ route('admin.reviews.destroy', $review->id) }}" data-confirm="Delete this review?">
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

{{-- ── Reviews CSV Import Modal ─────────────────────────────────────── --}}
<div class="modal fade" id="importReviewsModal" tabindex="-1" aria-labelledby="importReviewsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.reviews.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content shadow border-0" style="border-radius:12px;">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold" id="importReviewsModalLabel">Import Reviews (CSV)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="small text-muted mb-3">
                        Upload a CSV file to add reviews for your products without requiring customer user accounts. The specified <code>user_name</code> will display publicly on your storefront.
                    </div>
                    <div class="p-2 mb-3 bg-light rounded border" style="font-size:0.75rem;">
                        <div class="fw-bold mb-1">Supported CSV Columns:</div>
                        <code>sku</code> (or <code>product_id</code>), <code>user_name</code>, <code>rating</code> (1-5), <code>title</code>, <code>comment</code>, <code>is_verified_purchase</code> (1 or 0), <code>status</code> (approved/pending), <code>created_at</code> (optional date)
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Choose CSV File</label>
                        <input type="file" name="import_file" class="form-control form-control-sm" accept=".csv,.txt" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Import Reviews</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
