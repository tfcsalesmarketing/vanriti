@php
    $product = $product ?? null;
    $selectedCategories = $product ? $product->categories->pluck('id')->toArray() : old('category_ids', []);
    $primaryCategoryId = $product ? ($product->categories->where('pivot.is_primary', true)->first()?->id ?? ($selectedCategories[0] ?? null)) : old('primary_category_id');
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Basic Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $product->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">SKU</label>
                        <input type="text" name="sku" class="form-control form-control-sm" value="{{ old('sku', $product->sku ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="draft" {{ old('status', $product->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ old('status', $product->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $product->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Short Description</label>
                        <textarea name="short_description" class="form-control form-control-sm" rows="2">{{ old('short_description', $product->short_description ?? '') }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control form-control-sm" rows="4">{{ old('description', $product->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Pricing</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">MRP <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="mrp" id="formMrp" class="form-control form-control-sm" value="{{ old('mrp', $product->mrp ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Selling Price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="selling_price" id="formSellingPrice" class="form-control form-control-sm" value="{{ old('selling_price', $product->selling_price ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">GST Rate (%)</label>
                        <input type="number" step="0.01" name="gst_rate" class="form-control form-control-sm" value="{{ old('gst_rate', $product->gst_rate ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Discount %</label>
                        <input type="text" id="formDiscountDisplay" class="form-control form-control-sm bg-light" value="{{ old('discount_percent', $product->discount_percent ?? 0) }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Content</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Highlights</label>
                        <textarea name="highlights" class="form-control form-control-sm" rows="3">{{ old('highlights', $product->highlights ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Ingredients</label>
                        <textarea name="ingredients" class="form-control form-control-sm" rows="3">{{ old('ingredients', $product->ingredients ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Benefits</label>
                        <textarea name="benefits" class="form-control form-control-sm" rows="3">{{ old('benefits', $product->benefits ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">How to Use</label>
                        <textarea name="how_to_use" class="form-control form-control-sm" rows="3">{{ old('how_to_use', $product->how_to_use ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Directions</label>
                        <textarea name="directions" class="form-control form-control-sm" rows="3">{{ old('directions', $product->directions ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Warnings</label>
                        <textarea name="warnings" class="form-control form-control-sm" rows="3">{{ old('warnings', $product->warnings ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Precautions</label>
                        <textarea name="precautions" class="form-control form-control-sm" rows="3">{{ old('precautions', $product->precautions ?? '') }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Disclaimer</label>
                        <textarea name="disclaimer" class="form-control form-control-sm" rows="2">{{ old('disclaimer', $product->disclaimer ?? '') }}</textarea>
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
                        <input type="text" name="meta_title" class="form-control form-control-sm" value="{{ old('meta_title', $product->meta_title ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Meta Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control form-control-sm" value="{{ old('meta_keywords', $product->meta_keywords ?? '') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Meta Description</label>
                        <textarea name="meta_description" class="form-control form-control-sm" rows="2">{{ old('meta_description', $product->meta_description ?? '') }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Search Keywords</label>
                        <input type="text" name="search_keywords" class="form-control form-control-sm" value="{{ old('search_keywords', $product->search_keywords ?? '') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Stock & Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Stock</label>
                        <input type="number" name="stock" class="form-control form-control-sm" value="{{ old('stock', $product->stock ?? 0) }}" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" class="form-control form-control-sm" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 5) }}" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">HSN Code</label>
                        <input type="text" name="hsn_code" class="form-control form-control-sm" value="{{ old('hsn_code', $product->hsn_code ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Net Quantity</label>
                        <input type="text" name="net_quantity" class="form-control form-control-sm" value="{{ old('net_quantity', $product->net_quantity ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Unit</label>
                        <input type="text" name="unit" class="form-control form-control-sm" value="{{ old('unit', $product->unit ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Shelf Life</label>
                        <input type="text" name="shelf_life" class="form-control form-control-sm" value="{{ old('shelf_life', $product->shelf_life ?? '') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Manufacturer</label>
                        <input type="text" name="manufacturer" class="form-control form-control-sm" value="{{ old('manufacturer', $product->manufacturer ?? '') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Manufacturer Address</label>
                        <input type="text" name="manufacturer_address" class="form-control form-control-sm" value="{{ old('manufacturer_address', $product->manufacturer_address ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Country of Origin</label>
                        <input type="text" name="country_of_origin" class="form-control form-control-sm" value="{{ old('country_of_origin', $product->country_of_origin ?? '') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Categories</div>
            <div class="card-body">
                <label class="form-label small fw-semibold">Assign Categories <span class="text-danger">*</span></label>
                <div class="border rounded-3 p-2" style="max-height:220px;overflow-y:auto;">
                    @php
                        $roots = collect($categories)->where('parent_id', null);
                    @endphp
                    @foreach ($roots as $root)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="category_ids[]" value="{{ $root->id }}" id="cat_{{ $root->id }}" {{ in_array($root->id, $selectedCategories) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="cat_{{ $root->id }}">{{ $root->name }}</label>
                        </div>
                        @php
                            $children = collect($categories)->where('parent_id', $root->id);
                        @endphp
                        @foreach ($children as $child)
                            <div class="form-check ms-3">
                                <input class="form-check-input" type="checkbox" name="category_ids[]" value="{{ $child->id }}" id="cat_{{ $child->id }}" {{ in_array($child->id, $selectedCategories) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="cat_{{ $child->id }}">{{ $child->name }}</label>
                            </div>
                            @php
                                $grandchildren = collect($categories)->where('parent_id', $child->id);
                            @endphp
                            @foreach ($grandchildren as $gc)
                                <div class="form-check ms-4">
                                    <input class="form-check-input" type="checkbox" name="category_ids[]" value="{{ $gc->id }}" id="cat_{{ $gc->id }}" {{ in_array($gc->id, $selectedCategories) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="cat_{{ $gc->id }}">{{ $gc->name }}</label>
                                </div>
                            @endforeach
                        @endforeach
                    @endforeach
                </div>
                <div class="form-text">Click checkboxes to select multiple categories.</div>

                <label class="form-label small fw-semibold mt-2">Primary Category</label>
                <select name="primary_category_id" class="form-select form-select-sm">
                    <option value="">None</option>
                    @foreach ($categories as $cat)
                        @if ($cat->parent_id)
                            @php $parent = collect($categories)->firstWhere('id', $cat->parent_id); @endphp
                            <option value="{{ $cat->id }}" {{ $primaryCategoryId == $cat->id ? 'selected' : '' }}>{{ $parent?->name ?? '—' }} / {{ $cat->name }}</option>
                        @else
                            <option value="{{ $cat->id }}" {{ $primaryCategoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Flags</div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="flagFeatured" {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="flagFeatured">Featured</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_bestseller" value="1" id="flagBestseller" {{ old('is_bestseller', $product->is_bestseller ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="flagBestseller">Bestseller</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_new_arrival" value="1" id="flagNewArrival" {{ old('is_new_arrival', $product->is_new_arrival ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="flagNewArrival">New Arrival</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Save Product</button>
    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mrp = document.getElementById('formMrp');
    const sp = document.getElementById('formSellingPrice');
    const display = document.getElementById('formDiscountDisplay');
    function calc() {
        const m = parseFloat(mrp.value) || 0;
        const s = parseFloat(sp.value) || 0;
        if (m > 0 && m >= s) {
            display.value = Math.round(((m - s) / m) * 1000) / 10 + '%';
        } else {
            display.value = '0%';
        }
    }
    if (mrp) mrp.addEventListener('input', calc);
    if (sp) sp.addEventListener('input', calc);
    calc();
});
</script>
@endpush
