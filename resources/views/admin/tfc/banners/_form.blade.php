@php
    $banner = $banner ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Banner Visuals &amp; Messaging</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Headline Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $banner->title ?? '') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Subtitle / Descriptive Text</label>
                        <textarea name="subtitle" class="form-control" rows="2">{{ old('subtitle', $banner->subtitle ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Desktop Banner Image URL <span class="text-danger">*</span></label>
                        <input type="url" name="image" class="form-control" value="{{ old('image', $banner->image ?? '') }}" required placeholder="https://...">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Mobile Banner Image URL (Optional)</label>
                        <input type="url" name="mobile_image" class="form-control" value="{{ old('mobile_image', $banner->mobile_image ?? '') }}" placeholder="https://...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Call to Action (CTA) Link</label>
                        <input type="text" name="link" class="form-control" value="{{ old('link', $banner->link ?? '') }}" placeholder="/opportunity or https://...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">CTA Button Text</label>
                        <input type="text" name="link_text" class="form-control" value="{{ old('link_text', $banner->link_text ?? 'Learn More') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Position &amp; Status</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Position Slot <span class="text-danger">*</span></label>
                    <select name="position" class="form-select" required>
                        <option value="hero" {{ old('position', $banner->position ?? 'hero') === 'hero' ? 'selected' : '' }}>Homepage Hero Slider</option>
                        <option value="opportunity" {{ old('position', $banner->position ?? '') === 'opportunity' ? 'selected' : '' }}>Opportunity Page Banner</option>
                        <option value="cta" {{ old('position', $banner->position ?? '') === 'cta' ? 'selected' : '' }}>Footer Promotional CTA</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Display Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $banner->sort_order ?? 0) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status', $banner->status ?? 'active') === 'active' ? 'selected' : '' }}>Active (Visible)</option>
                        <option value="inactive" {{ old('status', $banner->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3 mb-5">
    <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check-lg me-1"></i>Save Banner</button>
    <a href="{{ route('admin.tfc.banners.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
