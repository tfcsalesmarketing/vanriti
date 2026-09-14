@extends('admin.layouts.app')

@section('title', 'Media Library')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <div>
        <h5 class="mb-0 fw-bold">Media Library</h5>
        <small class="text-muted"><span class="badge badge-count">{{ $media->total() }}</span> images total</small>
    </div>
    <a href="{{ route('admin.media.export') }}" class="btn btn-sm btn-soft-success">
        <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
    </a>
</div>

<div class="card upload-card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3 gap-2">
            <h6 class="fw-bold mb-0"><i class="bi bi-cloud-arrow-up me-1 text-success"></i>Upload Images</h6>
            <span class="small text-muted d-none d-sm-inline">Drop images below</span>
        </div>
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-12">
                <input type="file" name="images[]" id="images" class="d-none" accept="image/jpeg,image/png,image/webp,image/gif" multiple required data-dropzone data-required-msg="Please select at least one image">
            </div>
            <div class="col-md-6">
                <label for="name" class="form-label small text-muted mb-1">Image Name <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Hero banner flower" maxlength="150">
                <div class="form-text small">Applied to all selected images.</div>
            </div>
            <div class="col-md-6 d-flex align-items-end justify-content-md-end">
                <button type="submit" class="btn btn-primary px-4 w-100 w-md-auto">
                    <i class="bi bi-cloud-arrow-up me-1"></i>Upload Images
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="card-body pb-0">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search by name or file..." value="{{ request('q') }}" form="mediaSearch">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" form="mediaSearch" class="btn btn-sm btn-dark w-100"><i class="bi bi-search me-1"></i>Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.media.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
            </div>
        </div>
        <form id="mediaSearch" method="GET" action="{{ route('admin.media.index') }}" class="d-none"></form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Name</th>
                    <th>Secondary Name</th>
                    <th>File</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Uploaded By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($media as $item)
                    <tr>
                        <td>
                            <img src="{{ $item->url }}" alt="{{ $item->name }}" class="img-thumb" loading="lazy">
                        </td>
                        <td class="fw-semibold small media-name-cell">{{ $item->name }}</td>
                        <td class="small text-muted media-secondary-cell">
                            @if ($item->secondary_name)
                                <span class="mime-badge">{{ $item->secondary_name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small text-muted text-truncate" style="max-width:140px;" title="{{ $item->file_name }}">{{ $item->file_name }}</td>
                        <td class="small"><span class="mime-badge">{{ $item->mime_type }}</span></td>
                        <td class="small">{{ $item->size_label }}</td>
                        <td class="small text-muted">
                            {{ $item->admin?->name ?? '—' }}
                            <div class="text-muted">{{ $item->created_at?->format('d M Y') }}</div>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <button type="button"
                                        class="btn btn-sm btn-soft-success copy-link"
                                        data-url="{{ $item->url }}"
                                        title="Copy link">
                                    <i class="bi bi-link-45deg"></i><span class="copy-label ms-1">Copy Link</span>
                                </button>
                                <form method="POST" action="{{ route('admin.media.destroy', $item) }}" data-confirm="Delete this image? This cannot be undone.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-image d-block fs-3 mb-2 opacity-50"></i>
                            No images found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $media->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.copy-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-url');
            var label = btn.querySelector('.copy-label');

            function done() {
                if (label) {
                    label.textContent = 'Copied!';
                    setTimeout(function () { label.textContent = 'Copy Link'; }, 1500);
                }
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(done, done);
                return;
            }

            var textarea = document.createElement('textarea');
            textarea.value = url;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(textarea);
            done();
        });
    });
})();
</script>
@endpush