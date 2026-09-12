@php
    $category = $category ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Basic Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $category->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="active" {{ old('status', $category->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $category->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control form-control-sm" rows="3">{{ old('description', $category->description ?? '') }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Image</label>
                        <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                        @if ($category && $category->image)
                            <div class="mt-2"><img src="{{ image_url($category->image) }}" alt="" class="img-thumb"></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">SEO</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control form-control-sm" value="{{ old('meta_title', $category->meta_title ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Meta Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control form-control-sm" value="{{ old('meta_keywords', $category->meta_keywords ?? '') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Meta Description</label>
                        <textarea name="meta_description" class="form-control form-control-sm" rows="2">{{ old('meta_description', $category->meta_description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Save Main Category</button>
    <a href="{{ route('admin.main-categories.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
</div>
