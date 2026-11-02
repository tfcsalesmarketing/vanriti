@extends('admin.layouts.app')

@section('title', 'Instagram Reels')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
            <i class="bi bi-instagram text-danger"></i> Instagram Reels
        </h4>
        <small class="text-muted">Curate which reels appear on your homepage "Watch &amp; Shop" section</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('home') }}#vrReelsSection" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-box-arrow-up-right me-1"></i> View on Homepage
        </a>
        <a href="{{ route('admin.reels.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Reel
        </a>
    </div>
</div>

{{-- Metric Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-xs h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Reels</span>
                    <h3 class="fw-bold mb-0 mt-1">{{ $totalCount }}</h3>
                </div>
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary" style="width:44px;height:44px;">
                    <i class="bi bi-film fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-xs h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Active on Website</span>
                    <h3 class="fw-bold text-success mb-0 mt-1">{{ $activeCount }}</h3>
                </div>
                <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center text-success" style="width:44px;height:44px;">
                    <i class="bi bi-check-circle fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-xs h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Hidden / Draft</span>
                    <h3 class="fw-bold text-muted mb-0 mt-1">{{ $inactiveCount }}</h3>
                </div>
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-muted" style="width:44px;height:44px;">
                    <i class="bi bi-eye-slash fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-xs">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">Preview</th>
                    <th>Reel Title &amp; Link</th>
                    <th>Tagged Product (Shoppable)</th>
                    <th style="width: 100px;">Views</th>
                    <th style="width: 90px;">Sort Order</th>
                    <th style="width: 120px;">Website Status</th>
                    <th class="text-end" style="width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reels as $reel)
                    <tr>
                        <td>
                            <div class="position-relative rounded overflow-hidden shadow-xs bg-dark" style="width: 54px; height: 80px;">
                                <img src="{{ $reel->poster }}" alt="" class="w-100 h-100 object-fit-cover" loading="lazy">
                                <div class="position-absolute top-50 start-50 translate-middle text-white" style="text-shadow: 0 1px 3px rgba(0,0,0,0.8);">
                                    <i class="bi bi-play-circle-fill fs-5"></i>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $reel->title ?: 'Untitled Reel' }}</div>
                            @if ($reel->instagram_url)
                                <a href="{{ $reel->instagram_url }}" target="_blank" class="small text-danger text-decoration-none d-inline-flex align-items-center gap-1 mt-0.5">
                                    <i class="bi bi-instagram"></i>
                                    <span>View on Instagram</span>
                                    <i class="bi bi-arrow-up-right" style="font-size: 0.7rem;"></i>
                                </a>
                            @elseif ($reel->video_url)
                                <span class="badge bg-light text-secondary border small mt-0.5">Custom MP4 Video</span>
                            @endif
                        </td>
                        <td>
                            @if ($reel->product)
                                <div class="d-flex align-items-center gap-2">
                                    @php $prodImg = $reel->product->getPrimaryImage()?->image_path; @endphp
                                    @if ($prodImg)
                                        <img src="{{ image_url($prodImg) }}" alt="" class="rounded border" style="width: 36px; height: 36px; object-fit: cover;">
                                    @endif
                                    <div>
                                        <div class="small fw-semibold text-dark text-truncate" style="max-width: 220px;" title="{{ $reel->product->name }}">
                                            {{ $reel->product->name }}
                                        </div>
                                        <div class="text-success small fw-bold">{{ format_price($reel->product->selling_price) }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small"><em>No product tagged</em></span>
                            @endif
                        </td>
                        <td class="small text-muted">
                            {{ $reel->views_count ?: '—' }}
                        </td>
                        <td class="small fw-semibold text-dark">
                            {{ $reel->sort_order }}
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.reels.toggle', $reel) }}" class="m-0 js-reel-toggle-form">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $reel->is_active ? 'btn-success-subtle text-success border border-success-subtle' : 'btn-light text-muted border' }} py-0.5 px-2 rounded-pill small">
                                    <i class="bi {{ $reel->is_active ? 'bi-check-circle-fill' : 'bi-dash-circle' }} me-1"></i>
                                    {{ $reel->is_active ? 'Active' : 'Hidden' }}
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.reels.edit', $reel) }}" class="btn btn-outline-secondary" title="Edit Reel">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.reels.destroy', $reel) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this reel?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Delete Reel">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-film fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <p class="mb-2 fw-semibold">No Instagram reels added yet.</p>
                            <p class="small text-muted mb-3">Add reels from your Instagram profile to showcase customer reviews, beauty tips, and shoppable routines!</p>
                            <a href="{{ route('admin.reels.create') }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> Add Your First Reel
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($reels->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $reels->links() }}
        </div>
    @endif
</div>
@endsection

