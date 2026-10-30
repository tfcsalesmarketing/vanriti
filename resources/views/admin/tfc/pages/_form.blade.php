@php
    $page = $page ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Page Content</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Page Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $page->title ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">URL Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $page->slug ?? '') }}">
                        <div class="form-text">Leave empty to auto-slugify.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Summary / Excerpt</label>
                        <textarea name="excerpt" class="form-control" rows="2">{{ old('excerpt', $page->excerpt ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Body Content (HTML / Rich Text)</label>
                        <textarea name="content" class="form-control font-monospace" rows="14">{{ old('content', $page->content ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Status &amp; Safeguards</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Publication Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="draft" {{ old('status', $page->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft (Safeguarded)</option>
                        <option value="published" {{ old('status', $page->status ?? '') === 'published' ? 'selected' : '' }}>Published Live</option>
                    </select>
                    <div class="form-text text-danger small mt-1">
                        <i class="bi bi-shield-exclamation me-1"></i>Keep in <strong>Draft</strong> if statutory details, officer appointments or cooling-off terms are unconfirmed.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Display Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $page->sort_order ?? 0) }}">
                </div>
            </div>
        </div>

        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Search Engine Optimization</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $page->meta_title ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="3">{{ old('meta_description', $page->meta_description ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3 mb-5">
    <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check-lg me-1"></i>Save Page</button>
    <a href="{{ route('admin.tfc.pages.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
