@extends('admin.layouts.app')

@section('title', 'Media Library')

@section('content')
<div class="media-page">

    {{-- ===== Page Header ===== --}}
    <div class="media-page-header mb-4">
        <div class="media-page-header-inner">
            <div class="media-header-icon">
                <i class="bi bi-images"></i>
            </div>
            <div class="media-header-text">
                <h4 class="mb-0 fw-bold">Media Library</h4>
                <span class="media-header-sub">{{ $media->total() }} image{{ $media->total() === 1 ? '' : 's' }} on file</span>
            </div>
            <div class="media-header-actions ms-auto">
                <a href="{{ route('admin.media.export') }}" class="btn media-btn-soft">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                </a>
            </div>
        </div>
    </div>

    {{-- ===== Upload Panel ===== --}}
    <div class="media-upload-card mb-4">
        <div class="media-upload-top">
            <div class="media-upload-title">
                <i class="bi bi-cloud-arrow-up-fill"></i>
                <div>
                    <h6 class="mb-0 fw-semibold">Upload new images</h6>
                    <small class="text-muted">Drop files, or click to browse — add a secondary name to each preview</small>
                </div>
            </div>
            <span class="media-upload-badge"><i class="bi bi-shield-check me-1"></i>JPG · PNG · WEBP · GIF</span>
        </div>

        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="media-upload-col-main">
                <input type="file" name="images[]" id="images" class="d-none"
                       accept="image/jpeg,image/png,image/webp,image/gif" multiple required
                       data-dropzone data-required-msg="Please select at least one image">
            </div>
            <div class="media-upload-col-side">
                <label for="name" class="form-label media-field-label">Image Name <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Hero banner flower" maxlength="150">
                <div class="media-field-hint">Applied to every image in this batch.</div>
                <button type="submit" class="btn media-btn-primary w-100 mt-2">
                    <i class="bi bi-cloud-arrow-up me-1"></i>Upload Images
                </button>
            </div>
        </form>
    </div>

    {{-- ===== Toolbar ===== --}}
    <div class="media-toolbar mb-3">
        <form method="GET" action="{{ route('admin.media.index') }}" class="d-flex align-items-center gap-2 flex-grow-1">
            <div class="media-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="q" class="form-control media-search-input" placeholder="Search by name or file…" value="{{ request('q') }}">
            </div>
            <button type="submit" class="btn media-btn-dark"><i class="bi bi-search"></i></button>
            @if (request('q'))
                <a href="{{ route('admin.media.index') }}" class="btn media-btn-ghost" title="Clear search"><i class="bi bi-x-lg"></i></a>
            @endif
        </form>
        <div class="media-view-toggle ms-auto" role="group">
            <button type="button" class="media-view-btn active" data-view="grid" title="Grid view"><i class="bi bi-grid-3x3-gap-fill"></i></button>
            <button type="button" class="media-view-btn" data-view="list" title="List view"><i class="bi bi-list-ul"></i></button>
        </div>
    </div>

    {{-- ===== Media Grid / List ===== --}}
    <div class="media-grid" id="mediaGrid" data-view="grid">
        @forelse ($media as $item)
            <div class="media-card" data-url="{{ $item->url }}" data-name="{{ $item->name }}" data-id="{{ $item->id }}">
                <div class="media-thumb" style="--thumb-bg: {{ $loop->index % 4 === 1 ? '#eef2e3' : ($loop->index % 4 === 2 ? '#f7f4ea' : ($loop->index % 4 === 3 ? '#efece5' : '#f2f0ec')) }};">
                    <img src="{{ $item->url }}" alt="{{ $item->name }}" loading="lazy">
                    <div class="media-thumb-overlay">
                        <button type="button" class="media-ov-btn media-preview" title="Preview image"><i class="bi bi-arrows-fullscreen"></i></button>
                        <button type="button" class="media-ov-btn media-copy-link copy-link" data-url="{{ $item->url }}" title="Copy link"><i class="bi bi-link-45deg"></i></button>
                    </div>
                    <span class="media-thumb-badge"><i class="bi bi-image"></i></span>
                </div>
                <div class="media-card-body">
                    <div class="media-card-name" title="{{ $item->name }}">{{ $item->name }}</div>
                    <div class="media-card-secondary">
                        @if ($item->secondary_name)
                            <span class="media-chip">{{ $item->secondary_name }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                    <div class="media-card-meta">
                        <span class="media-meta-chip">{{ $item->mime_type }}</span>
                        <span class="media-meta-item"><i class="bi bi-hdd"></i> {{ $item->size_label }}</span>
                    </div>
                    <div class="media-card-footer">
                        <span class="media-meta-item" title="{{ $item->file_name }}"><i class="bi bi-person"></i> {{ $item->admin?->name ?? 'System' }}</span>
                        <span class="media-meta-item"><i class="bi bi-calendar3"></i> {{ $item->created_at?->format('d M Y') }}</span>
                        <button type="button" class="media-delete-trigger" data-bs-toggle="tooltip" title="Delete image">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="media-empty col-12">
                <div class="media-empty-icon"><i class="bi bi-images"></i></div>
                <h6 class="fw-semibold">No images found</h6>
                <p class="text-muted small mb-0">Upload images above, or adjust your search to see them here.</p>
            </div>
        @endforelse
    </div>

    {{-- ===== Pagination ===== --}}
    @if ($media->hasPages())
        <div class="media-pagination">
            {{ $media->links() }}
        </div>
    @endif

</div>

{{-- Hidden delete forms (submitted via the card trash trigger) --}}
<div class="d-none">
    @foreach ($media as $item)
        <form id="mediaDeleteForm_{{ $item->id }}" method="POST" action="{{ route('admin.media.destroy', $item) }}" data-confirm="Delete this image? This cannot be undone.">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</div>

{{-- ===== Lightbox ===== --}}
<div class="media-lightbox" id="mediaLightbox" aria-hidden="true">
    <button type="button" class="media-lb-close" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    <div class="media-lb-stage">
        <img src="" alt="" class="media-lb-img">
    </div>
    <div class="media-lb-caption"></div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ---- Copy link (with visible affordance on overlay button) ----
    document.querySelectorAll('.copy-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-url');
            var icon = btn.querySelector('i');

            function done() {
                if (icon) {
                    icon.classList.remove('bi-link-45deg');
                    icon.classList.add('bi-check-lg');
                    setTimeout(function () {
                        icon.classList.remove('bi-check-lg');
                        icon.classList.add('bi-link-45deg');
                    }, 1600);
                }
                if (typeof showToast === 'function') {
                    showToast('Link copied to clipboard.', 'success');
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

    // ---- View toggle (grid / list) ----
    var grid = document.getElementById('mediaGrid');
    var viewBtns = document.querySelectorAll('.media-view-btn');
    viewBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            viewBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            grid.dataset.view = btn.dataset.view;
        });
    });

    // ---- Media card delete trigger -> hidden form submit (custom confirm) ----
    document.querySelectorAll('.media-delete-trigger').forEach(function (trig) {
        trig.addEventListener('click', function () {
            var card = trig.closest('.media-card');
            var form = document.getElementById('mediaDeleteForm_' + card.dataset.id);
            if (!form) return;
            if (typeof vrConfirm === 'function') {
                vrConfirm('Delete this image? This cannot be undone.').then(function (ok) {
                    if (ok) form.submit();
                });
            } else if (window.confirm('Delete this image? This cannot be undone.')) {
                form.submit();
            }
        });
    });

    // ---- Lightbox ----
    var lightbox = document.getElementById('mediaLightbox');
    if (lightbox) {
        function openLightbox(url, name) {
            var img = lightbox.querySelector('.media-lb-img');
            var caption = lightbox.querySelector('.media-lb-caption');
            img.src = url;
            caption.textContent = name || '';
            lightbox.classList.add('open');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.classList.remove('open');
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            setTimeout(function () { lightbox.querySelector('.media-lb-img').removeAttribute('src'); }, 250);
        }

        document.querySelectorAll('.media-preview').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var card = btn.closest('.media-card');
                openLightbox(card.dataset.url, card.dataset.name);
            });
        });

        lightbox.querySelector('.media-lb-close').addEventListener('click', closeLightbox);
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox || e.target === lightbox.querySelector('.media-lb-stage')) closeLightbox();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && lightbox.classList.contains('open')) closeLightbox();
        });
    }
})();
</script>
@endpush