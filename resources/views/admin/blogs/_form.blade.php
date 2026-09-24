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
                        <textarea name="content" class="form-control form-control-sm" rows="12" required>{{ old('content', $blog->content ?? '') }}</textarea>
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
                    <label class="form-label small fw-semibold">SEO Title</label>
                    <input type="text" name="seo_title" class="form-control form-control-sm" value="{{ old('seo_title', $blog->seo_title ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Meta Description</label>
                    <textarea name="meta_description" class="form-control form-control-sm" rows="3">{{ old('meta_description', $blog->meta_description ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Keywords</label>
                    <textarea name="keywords" class="form-control form-control-sm" rows="3">{{ old('keywords', $blog->keywords ?? '') }}</textarea>
                </div>
            </div>
        </div>

        @if ($blog && $blog->featured_image)
            <div class="card mb-3">
                <div class="card-body">
                    <img src="{{ image_url($blog->featured_image) }}" alt="" class="img-fluid rounded">
                </div>
            </div>
        @endif
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Save Blog</button>
    <a href="{{ route('admin.blogs.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
</div>