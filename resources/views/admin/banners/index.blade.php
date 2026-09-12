@extends('admin.layouts.app')

@section('title', 'Banners')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Banners</h5>
        <small class="text-muted">{{ $banners->total() }} banners total</small>
    </div>
    <a href="{{ route('admin.banners.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Banner</a>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Link</th>
                    <th>Position</th>
                    <th>Type</th>
                    <th>Sort</th>
                    <th>Status</th>
                    <th>Dates</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($banners as $banner)
                    <tr>
                        <td>
                            @if ($banner->image)
                                <img src="{{ image_url($banner->image) }}" alt="" class="img-thumb">
                            @else
                                <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            @endif
                        </td>
                        <td class="small">
                            @if ($banner->link)
                                <a href="{{ $banner->link }}" target="_blank" class="text-truncate d-inline-block" style="max-width:200px;">{{ $banner->link }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="small">{{ $banner->position }}</td>
                        <td>
                            <span class="badge badge-soft-primary">{{ ucfirst($banner->type) }}</span>
                        </td>
                        <td class="small">{{ $banner->sort_order }}</td>
                        <td>
                            <span class="badge {{ $banner->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ ucfirst($banner->status) }}</span>
                        </td>
                        <td class="small text-muted">
                            @if ($banner->starts_at || $banner->expires_at)
                                <div>{{ $banner->starts_at?->format('d M Y') ?: '—' }}</div>
                                <div>{{ $banner->expires_at?->format('d M Y') ?: '—' }}</div>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Delete this banner?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No banners found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $banners->links() }}
    </div>
</div>
@endsection