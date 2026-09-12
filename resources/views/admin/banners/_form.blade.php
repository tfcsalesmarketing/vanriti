@php
    $banner = $banner ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Banner Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ old('sort_order', $banner->sort_order ?? 0) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select form-select-sm" required>
                            <option value="hero" {{ old('type', $banner->type ?? 'hero') === 'hero' ? 'selected' : '' }}>Hero</option>
                            <option value="promotional" {{ old('type', $banner->type ?? '') === 'promotional' ? 'selected' : '' }}>Promotional</option>
                            <option value="section" {{ old('type', $banner->type ?? '') === 'section' ? 'selected' : '' }}>Section</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Position <span class="text-danger">*</span></label>
                        <input type="text" name="position" class="form-control form-control-sm" value="{{ old('position', $banner->position ?? 'home_top') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Link URL <span class="text-danger">*</span></label>
                        <input type="url" name="link" class="form-control form-control-sm" value="{{ old('link', $banner->link ?? '') }}" placeholder="https://..." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="active" {{ old('status', $banner->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $banner->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Starts At</label>
                        <input type="date" name="starts_at" class="form-control form-control-sm" value="{{ old('starts_at', $banner?->starts_at?->format('Y-m-d') ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Expires At</label>
                        <input type="date" name="expires_at" class="form-control form-control-sm" value="{{ old('expires_at', $banner?->expires_at?->format('Y-m-d') ?? '') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Images</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Image <span class="text-danger">*</span></label>
                    <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                    @if ($banner && $banner->image)
                        <div class="mt-2"><img src="{{ image_url($banner->image) }}" alt="" class="img-thumb"></div>
                    @endif
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Mobile Image</label>
                    <input type="file" name="mobile_image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                    @if ($banner && $banner->mobile_image)
                        <div class="mt-2"><img src="{{ image_url($banner->mobile_image) }}" alt="" class="img-thumb"></div>
                    @endif
                </div>
                <div class="form-text">Upload may be used as-is, or if no image file is attached supply a direct URL below.</div>
                <div class="mb-3 mt-2">
                    <label class="form-label small fw-semibold">Image URL</label>
                    <input type="text" name="image_url" class="form-control form-control-sm" value="{{ old('image_url') }}" placeholder="https://...">
                </div>
                <div>
                    <label class="form-label small fw-semibold">Mobile Image URL</label>
                    <input type="text" name="mobile_image_url" class="form-control form-control-sm" value="{{ old('mobile_image_url') }}" placeholder="https://...">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Save Banner</button>
    <a href="{{ route('admin.banners.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
</div>