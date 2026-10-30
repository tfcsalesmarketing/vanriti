@extends('admin.layouts.app')

@section('title', 'TFC Banners & Hero Sliders')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-1">TFC Banners &amp; CTAs</h5>
        <p class="text-muted small mb-0">Manage visual hero slides, direct selling CTAs, and promotional headers.</p>
    </div>
    <a href="{{ route('admin.tfc.banners.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Banner
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 100px;">Preview</th>
                        <th>Headline &amp; Subtitle</th>
                        <th>Position</th>
                        <th>Target Link</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($banners as $banner)
                        <tr>
                            <td>
                                <img src="{{ image_url($banner->image) }}" alt="{{ $banner->title }}" class="rounded border" style="width: 80px; height: 45px; object-fit: cover;">
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $banner->title ?: '—' }}</div>
                                @if ($banner->subtitle)
                                    <div class="text-muted small text-truncate" style="max-width: 320px;">{{ $banner->subtitle }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ ucfirst($banner->position) }}</span>
                            </td>
                            <td class="small">
                                @if ($banner->link)
                                    <code>{{ $banner->link }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $banner->sort_order }}</td>
                            <td>
                                <span class="badge {{ $banner->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($banner->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.tfc.banners.edit', $banner) }}" class="btn btn-sm btn-outline-primary" title="Edit Banner">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('admin.tfc.banners.destroy', $banner) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this banner?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Banner">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No banners created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
