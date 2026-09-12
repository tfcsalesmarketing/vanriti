@extends('admin.layouts.app')

@section('title', 'Media Library')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Media Library</h5>
        <small class="text-muted">{{ $media->total() }} images total</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-upload me-1"></i>Upload Image</h6>
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label for="name" class="form-label small text-muted">Image Name</label>
                <input type="text" name="name" id="name" class="form-control form-control-sm" placeholder="e.g. Hero banner flower" maxlength="150" required>
            </div>
            <div class="col-md-5">
                <label for="image" class="form-label small text-muted">Image File</label>
                <input type="file" name="image" id="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-cloud-arrow-up me-1"></i>Upload</button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="card-body pb-0">
        <form method="GET" action="{{ route('admin.media.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by image name..." value="{{ request('q') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.media.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Name</th>
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
                        <td class="fw-semibold small">{{ $item->name }}</td>
                        <td class="small text-muted text-truncate" style="max-width:220px;" title="{{ $item->file_name }}">{{ $item->file_name }}</td>
                        <td class="small">{{ $item->mime_type }}</td>
                        <td class="small">{{ $item->size_label }}</td>
                        <td class="small text-muted">
                            {{ $item->admin?->name ?? '—' }}
                            <div class="text-muted">{{ $item->created_at?->format('d M Y') }}</div>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <button type="button"
                                        class="btn btn-sm btn-outline-success copy-link"
                                        data-url="{{ $item->url }}"
                                        title="Copy link">
                                    <i class="bi bi-link-45deg me-1"></i><span class="copy-label">Copy Link</span>
                                </button>
                                <form method="POST" action="{{ route('admin.media.destroy', $item) }}" onsubmit="return confirm('Delete this image? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No images found.</td>
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