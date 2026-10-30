@php
    $blog = $blog ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Article Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Article Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $blog->title ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $blog->slug ?? '') }}">
                        <div class="form-text">Leave empty to auto-slugify.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Featured Image URL</label>
                        <input type="url" name="featured_image" class="form-control" value="{{ old('featured_image', $blog->featured_image ?? '') }}">
                        <div class="form-text">Direct image URL (e.g. S3 or public asset).</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Excerpt / Teaser</label>
                        <textarea name="excerpt" class="form-control" rows="2">{{ old('excerpt', $blog->excerpt ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Article Content <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control font-monospace" rows="14" required>{{ old('content', $blog->content ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Publication Settings</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="draft" {{ old('status', $blog->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', $blog->status ?? '') === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Published Date &amp; Time</label>
                    <input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', isset($blog->published_at) ? $blog->published_at->format('Y-m-d\TH:i') : '') }}">
                </div>
            </div>
        </div>

        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Search Engine Optimization</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $blog->meta_title ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="3">{{ old('meta_description', $blog->meta_description ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3 mb-5">
    <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check-lg me-1"></i>Save Article</button>
    <a href="{{ route('admin.tfc.blogs.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
