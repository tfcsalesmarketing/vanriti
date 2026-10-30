@php
    $menu = $menu ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Navigation Item Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Navigation Label <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $menu->title ?? '') }}" required placeholder="e.g. Products, About Us">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Destination URL / Route Path <span class="text-danger">*</span></label>
                        <input type="text" name="url" class="form-control" value="{{ old('url', $menu->url ?? '') }}" required placeholder="e.g. /products, /opportunity, or https://...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Menu Placement Location <span class="text-danger">*</span></label>
                        <select name="location" class="form-select" required>
                            <option value="header" {{ old('location', $menu->location ?? 'header') === 'header' ? 'selected' : '' }}>Main Header Navbar</option>
                            <option value="footer_col1" {{ old('location', $menu->location ?? '') === 'footer_col1' ? 'selected' : '' }}>Footer Col 1 (Quick Links)</option>
                            <option value="footer_col2" {{ old('location', $menu->location ?? '') === 'footer_col2' ? 'selected' : '' }}>Footer Col 2 (Support &amp; Grievance)</option>
                            <option value="footer_legal" {{ old('location', $menu->location ?? '') === 'footer_legal' ? 'selected' : '' }}>Footer Legal &amp; Compliance</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Parent Dropdown Item (Optional)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">None (Top Level)</option>
                            @foreach ($parents as $p)
                                <option value="{{ $p->id }}" {{ old('parent_id', $menu->parent_id ?? '') == $p->id ? 'selected' : '' }}>{{ $p->title }} ({{ $p->location }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Settings &amp; Behavior</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Sort Order Position</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $menu->sort_order ?? 0) }}">
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" value="1" {{ old('is_active', $menu->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold small" for="isActiveCheck">Item is Active</label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="open_new_tab" id="openNewTabCheck" value="1" {{ old('open_new_tab', $menu->open_new_tab ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold small" for="openNewTabCheck">Open in New Browser Tab</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3 mb-5">
    <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check-lg me-1"></i>Save Menu Item</button>
    <a href="{{ route('admin.tfc.menus.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
