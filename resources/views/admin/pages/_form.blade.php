@php
    $page = $page ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Page Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title', $page->title ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Slug</label>
                        <input type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $page->slug ?? '') }}">
                        <div class="form-text">Leave blank to auto-generate.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="draft" {{ old('status', $page->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $page->status ?? '') === 'published' ? 'selected' : '' }}>Published</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Content</label>
                        <textarea name="content" class="form-control form-control-sm" rows="12">{{ old('content', $page->content ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold">SEO</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Meta Title</label>
                    <input type="text" name="meta_title" class="form-control form-control-sm" value="{{ old('meta_title', $page->meta_title ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Meta Description</label>
                    <textarea name="meta_description" class="form-control form-control-sm" rows="4">{{ old('meta_description', $page->meta_description ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Save Page</button>
    <a href="{{ route('admin.pages.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
</div>