@php
    $blog = $blog ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Post Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title', $blog->title ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Slug</label>
                        <input type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $blog->slug ?? '') }}">
                        <div class="form-text">Leave blank to auto-generate.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Category</label>
                        <select name="blog_category_id" class="form-select form-select-sm">
                            <option value="">No category</option>
                            @foreach ($blogCategories as $category)
                                <option value="{{ $category->id }}" {{ old('blog_category_id', $blog->blog_category_id ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Published At</label>
                        <input type="date" name="published_at" class="form-control form-control-sm" value="{{ old('published_at', $blog?->published_at?->format('Y-m-d') ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="draft" {{ old('status', $blog->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $blog->status ?? '') === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ old('status', $blog->status ?? '') === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Featured Image</label>
                        <input type="file" name="featured_image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Excerpt</label>
                        <textarea name="excerpt" class="form-control form-control-sm" rows="2" maxlength="500">{{ old('excerpt', $blog->excerpt ?? '') }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Content <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control form-control-sm js-rich-editor" data-min-height="380px" rows="12">{{ old('content', $blog->content ?? '') }}</textarea>
                        @error('content')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Featured Image Preview</div>
            <div class="card-body text-center">
                <div id="featuredImagePreviewBox" class="p-2 border rounded bg-light" style="min-height: 180px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    @if ($blog && $blog->featured_image)
                        <img id="featuredImagePreview" src="{{ image_url($blog->featured_image) }}" alt="Featured Image" class="img-fluid rounded" style="max-height: 220px; object-fit: cover; width: 100%;">
                    @else
                        <div id="featuredImagePlaceholder" class="text-muted small py-4">
                            <i class="bi bi-image fs-1 d-block mb-1 text-secondary"></i>
                            <span>No image selected</span>
                        </div>
                        <img id="featuredImagePreview" src="" alt="Featured Preview" class="img-fluid rounded d-none" style="max-height: 220px; object-fit: cover; width: 100%;">
                    @endif
                </div>
                <div class="form-text small mt-2">Recommended: 1200x675px (16:9 ratio). Formats: WEBP, JPG, PNG.</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">SEO & Meta</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">SEO Title</label>
                    <input type="text" name="seo_title" class="form-control form-control-sm" value="{{ old('seo_title', $blog->seo_title ?? '') }}" placeholder="Custom browser title...">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Meta Description</label>
                    <textarea name="meta_description" class="form-control form-control-sm" rows="3" placeholder="Brief summary for Google search snippets...">{{ old('meta_description', $blog->meta_description ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Keywords</label>
                    <textarea name="keywords" class="form-control form-control-sm" rows="2" placeholder="Comma-separated keywords (e.g. skincare, ayurveda)...">{{ old('keywords', $blog->keywords ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Save Blog</button>
    <a href="{{ route('admin.blogs.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css">
<style>
.vr-quill-wrapper {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    background: #ffffff;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}
.vr-quill-wrapper:focus-within {
    border-color: #059669;
    box-shadow: 0 0 0 0.2rem rgba(5, 150, 105, 0.15);
}
.vr-quill-wrapper .ql-toolbar.ql-snow {
    border: none;
    border-bottom: 1px solid #e9ecef;
    background: #f8fafc;
    padding: 8px 10px;
    position: sticky;
    top: 0;
    z-index: 10;
}
.vr-quill-wrapper .ql-container.ql-snow {
    border: none;
    font-family: inherit;
    font-size: 0.925rem;
}
.vr-quill-wrapper .ql-editor {
    min-height: 380px;
    padding: 16px 18px;
    line-height: 1.7;
}
.vr-quill-wrapper .ql-editor.ql-blank::before {
    font-style: normal;
    color: #94a3b8;
    left: 18px;
}
.vr-quill-wrapper .ql-toolbar .ql-formats {
    margin-right: 8px;
}
.vr-quill-wrapper .ql-snow .ql-stroke {
    stroke: #475569;
}
.vr-quill-wrapper .ql-snow .ql-fill {
    fill: #475569;
}
.vr-quill-wrapper .ql-snow .ql-picker {
    color: #475569;
}
.vr-quill-wrapper .ql-toolbar button:hover .ql-stroke,
.vr-quill-wrapper .ql-toolbar button.ql-active .ql-stroke {
    stroke: #059669;
}
.vr-quill-wrapper .ql-toolbar button:hover .ql-fill,
.vr-quill-wrapper .ql-toolbar button.ql-active .ql-fill {
    fill: #059669;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Quill editor on .js-rich-editor textareas
    if (typeof Quill !== 'undefined') {
        document.querySelectorAll('textarea.js-rich-editor').forEach(function (textarea) {
            textarea.style.display = 'none';

            const wrapper = document.createElement('div');
            wrapper.className = 'vr-quill-wrapper mb-2';

            const editorDiv = document.createElement('div');
            editorDiv.className = 'vr-quill-editor';
            const minHeight = textarea.dataset.minHeight || '380px';
            editorDiv.style.minHeight = minHeight;

            textarea.parentNode.insertBefore(wrapper, textarea);
            wrapper.appendChild(editorDiv);

            const rawContent = textarea.value || '';
            editorDiv.innerHTML = rawContent;

            const quill = new Quill(editorDiv, {
                theme: 'snow',
                placeholder: 'Write your inspiring blog article story here...',
                modules: {
                    toolbar: [
                        [{ 'header': [2, 3, 4, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        ['blockquote', 'code-block'],
                        [{ 'color': [] }, { 'background': [] }],
                        ['link', 'image'],
                        ['clean']
                    ]
                }
            });

            const syncContent = function () {
                const html = quill.root.innerHTML;
                const textOnly = quill.getText().trim();
                textarea.value = (html === '<p><br></p>' || textOnly === '') ? '' : html;
            };

            quill.on('text-change', syncContent);

            const form = textarea.closest('form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    syncContent();
                    const trimmed = textarea.value.trim();
                    if (!trimmed || trimmed === '<p><br></p>') {
                        if (textarea.name === 'content') {
                            e.preventDefault();
                            wrapper.style.borderColor = '#dc3545';
                            wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            let err = wrapper.parentElement.querySelector('.vr-editor-error');
                            if (!err) {
                                err = document.createElement('div');
                                err.className = 'text-danger small mt-1 vr-editor-error';
                                err.textContent = 'Please enter blog content.';
                                wrapper.parentElement.appendChild(err);
                            }
                        }
                    }
                });
            }
        });
    }

    // 2. Live image preview for Featured Image input
    const fileInput = document.querySelector('input[name="featured_image"]');
    const previewImg = document.getElementById('featuredImagePreview');
    const placeholder = document.getElementById('featuredImagePlaceholder');

    if (fileInput && previewImg) {
        fileInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('d-none');
                    if (placeholder) placeholder.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
@endpush