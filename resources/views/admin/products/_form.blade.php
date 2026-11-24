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
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="draft" {{ old('status', $product->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ old('status', $product->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $product->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Short Description</label>
                        <textarea name="short_description" class="form-control form-control-sm" rows="2" placeholder="Brief 1-2 sentence overview for cards and WhatsApp shares...">{{ old('short_description', $product->short_description ?? '') }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span>Description</span>
                            <span class="badge bg-light text-muted border fw-normal" style="font-size:0.7rem;"><i class="bi bi-pencil-square me-1 text-primary"></i>Rich Text Editor</span>
                        </label>
                        <textarea name="description" class="form-control form-control-sm js-rich-editor" data-min-height="180px" rows="4">{{ old('description', $product->description ?? '') }}</textarea>
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
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span>Content &amp; Ayurvedic Specifications</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.7rem;">
                    <i class="bi bi-magic me-1"></i>Formatted Text Editors Enabled
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span>Key Highlights</span>
                            <span class="badge bg-light text-muted border fw-normal" style="font-size:0.68rem;"><i class="bi bi-pencil-square me-1 text-primary"></i>Rich Text</span>
                        </label>
                        <textarea name="highlights" class="form-control form-control-sm js-rich-editor" data-min-height="120px" rows="3">{{ old('highlights', $product->highlights ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span>Ingredients</span>
                            <span class="badge bg-light text-muted border fw-normal" style="font-size:0.68rem;"><i class="bi bi-pencil-square me-1 text-primary"></i>Rich Text</span>
                        </label>
                        <textarea name="ingredients" class="form-control form-control-sm js-rich-editor" data-min-height="120px" rows="3">{{ old('ingredients', $product->ingredients ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span>Benefits</span>
                            <span class="badge bg-light text-muted border fw-normal" style="font-size:0.68rem;"><i class="bi bi-pencil-square me-1 text-primary"></i>Rich Text</span>
                        </label>
                        <textarea name="benefits" class="form-control form-control-sm js-rich-editor" data-min-height="120px" rows="3">{{ old('benefits', $product->benefits ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span>How to Use</span>
                            <span class="badge bg-light text-muted border fw-normal" style="font-size:0.68rem;"><i class="bi bi-pencil-square me-1 text-primary"></i>Rich Text</span>
                        </label>
                        <textarea name="how_to_use" class="form-control form-control-sm js-rich-editor" data-min-height="120px" rows="3">{{ old('how_to_use', $product->how_to_use ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span>Directions</span>
                            <span class="badge bg-light text-muted border fw-normal" style="font-size:0.68rem;"><i class="bi bi-pencil-square me-1 text-primary"></i>Rich Text</span>
                        </label>
                        <textarea name="directions" class="form-control form-control-sm js-rich-editor" data-min-height="120px" rows="3">{{ old('directions', $product->directions ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Warnings</span>
                            <span class="badge bg-light text-muted border fw-normal" style="font-size:0.68rem;"><i class="bi bi-pencil-square me-1 text-primary"></i>Rich Text</span>
                        </label>
                        <textarea name="warnings" class="form-control form-control-sm js-rich-editor" data-min-height="120px" rows="3">{{ old('warnings', $product->warnings ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span>Precautions</span>
                            <span class="badge bg-light text-muted border fw-normal" style="font-size:0.68rem;"><i class="bi bi-pencil-square me-1 text-primary"></i>Rich Text</span>
                        </label>
                        <textarea name="precautions" class="form-control form-control-sm js-rich-editor" data-min-height="120px" rows="3">{{ old('precautions', $product->precautions ?? '') }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span>Disclaimer</span>
                            <span class="badge bg-light text-muted border fw-normal" style="font-size:0.68rem;"><i class="bi bi-pencil-square me-1 text-primary"></i>Rich Text</span>
                        </label>
                        <textarea name="disclaimer" class="form-control form-control-sm js-rich-editor" data-min-height="100px" rows="2">{{ old('disclaimer', $product->disclaimer ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Product Video</div>
            <div class="card-body">
                @php $currentVideo = old('video_url', $product->video_url ?? ''); @endphp
                @if (filled($currentVideo))
                    <div class="alert alert-light border small py-2 mb-3 d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <span>Current video:
                            <a href="{{ video_url($currentVideo) }}" target="_blank" rel="noopener" class="text-decoration-underline">{{ Str::limit(video_url($currentVideo), 60) }}</a>
                        </span>
                        <div class="form-check small mb-0">
                            <input class="form-check-input" type="checkbox" name="remove_video" value="1" id="flagRemoveVideo">
                            <label class="form-check-label" for="flagRemoveVideo">Remove video</label>
                        </div>
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Video Link</label>
                        <input type="url" name="video_url" class="form-control form-control-sm" value="{{ old('video_url', $product->video_url ?? '') }}" placeholder="https://www.youtube.com/watch?v=... or https://your-cdn.com/product.mp4">
                        <div class="form-text">Paste a YouTube / Vimeo link or a direct .mp4 / .webm URL. Used as-is when no file is uploaded below.</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Upload Video</label>
                        <input type="file" name="video" class="form-control form-control-sm" accept="video/mp4,video/webm,video/ogg,video/quicktime,.mp4,.webm,.ogv,.mov">
                        <div class="form-text">MP4 / WebM / MOV / OGG, up to 50 MB. A saved file replaces the link above.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-shop me-1 text-primary"></i> Marketplace Purchase Links</span>
                <span class="badge bg-light text-muted border fw-normal" style="font-size:0.7rem;">Storefront Buy Buttons</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Provide direct product URLs to display <strong>&ldquo;Buy on Amazon&rdquo;</strong> and <strong>&ldquo;Buy on Flipkart&rdquo;</strong> buttons on the storefront product detail page. Ideal for shoppers who prefer purchasing on verified marketplaces. Leave empty to hide.
                </p>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-bag-check text-warning me-1"></i> Amazon Product URL</span>
                            @if ($product && filled($product->amazon_url))
                                <a href="{{ $product->amazon_url }}" target="_blank" rel="noopener noreferrer" class="small text-decoration-none" style="font-size:0.75rem;">
                                    <i class="bi bi-box-arrow-up-right me-0.5"></i> Test link
                                </a>
                            @endif
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-dark fw-bold" style="font-size: 0.78rem;">
                                Amazon.in
                            </span>
                            <input type="url" name="amazon_url" class="form-control form-control-sm" value="{{ old('amazon_url', $product->amazon_url ?? '') }}" placeholder="https://www.amazon.in/dp/B0...">
                        </div>
                        <div class="form-text">Direct product link on Amazon India (e.g. <code>https://www.amazon.in/dp/...</code>).</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-bag-check-fill text-primary me-1"></i> Flipkart Product URL</span>
                            @if ($product && filled($product->flipkart_url))
                                <a href="{{ $product->flipkart_url }}" target="_blank" rel="noopener noreferrer" class="small text-decoration-none" style="font-size:0.75rem;">
                                    <i class="bi bi-box-arrow-up-right me-0.5"></i> Test link
                                </a>
                            @endif
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-primary fw-bold" style="font-size: 0.78rem;">
                                Flipkart
                            </span>
                            <input type="url" name="flipkart_url" class="form-control form-control-sm" value="{{ old('flipkart_url', $product->flipkart_url ?? '') }}" placeholder="https://www.flipkart.com/.../p/itm...">
                        </div>
                        <div class="form-text">Direct product link on Flipkart (e.g. <code>https://www.flipkart.com/...</code>).</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Amazon A+ Brand Content Builder --}}
        @include('admin.products._a_plus_builder')

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
                        <label class="form-label small fw-semibold">Weight</label>
                        <input type="number" step="0.01" min="0" name="weight" class="form-control form-control-sm" value="{{ old('weight', $product->weight ?? '') }}" placeholder="e.g. 100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Unit</label>
                        <input type="text" name="unit" class="form-control form-control-sm" value="{{ old('unit', $product->unit ?? '') }}" placeholder="gm / ml / pcs">
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
    padding: 6px 8px;
}
.vr-quill-wrapper .ql-container.ql-snow {
    border: none;
    font-family: inherit;
    font-size: 0.875rem;
}
.vr-quill-wrapper .ql-editor {
    min-height: 120px;
    padding: 10px 12px;
    line-height: 1.5;
}
.vr-quill-wrapper .ql-editor.ql-blank::before {
    font-style: normal;
    color: #94a3b8;
    left: 12px;
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
    // 1. Calculate discount
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

    // 2. Initialize Rich Text Editor on all .js-rich-editor textareas
    if (typeof Quill !== 'undefined') {
        document.querySelectorAll('textarea.js-rich-editor').forEach(function (textarea) {
            // Hide textarea from view
            textarea.style.display = 'none';

            // Create wrapper element
            const wrapper = document.createElement('div');
            wrapper.className = 'vr-quill-wrapper mb-2';

            const editorDiv = document.createElement('div');
            editorDiv.className = 'vr-quill-editor';
            const minHeight = textarea.dataset.minHeight || '120px';
            editorDiv.style.minHeight = minHeight;

            // Put wrapper in DOM where textarea was
            textarea.parentNode.insertBefore(wrapper, textarea);
            wrapper.appendChild(editorDiv);

            // Populate initial content
            const rawContent = textarea.value || '';
            editorDiv.innerHTML = rawContent;

            // Instantiate Quill
            const quill = new Quill(editorDiv, {
                theme: 'snow',
                placeholder: 'Type or format ' + (textarea.getAttribute('name') || 'content') + '...',
                modules: {
                    toolbar: [
                        [{ 'header': [3, 4, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'color': [] }, { 'background': [] }],
                        ['clean']
                    ]
                }
            });

            // Live synchronize changes back to textarea
            const syncContent = function () {
                const html = quill.root.innerHTML;
                const textOnly = quill.getText().trim();
                textarea.value = (html === '<p><br></p>' || textOnly === '') ? '' : html;
            };

            quill.on('text-change', syncContent);

            // Also flush on form submission
            const form = textarea.closest('form');
            if (form) {
                form.addEventListener('submit', syncContent);
            }
        });
    }

    // 3. Category-Aware A+ Content Auto-Draft Helper
    const autoDraftBtn = document.getElementById('btnAutoDraftAPlus');
    if (autoDraftBtn) {
        autoDraftBtn.addEventListener('click', function () {
            const prodName = document.querySelector('input[name="name"]')?.value || 'Vanriti Formulation';
            const nameLower = prodName.toLowerCase();

            // Extract weight string
            const weightInput = document.querySelector('input[name="weight"]')?.value;
            const unitSelect = document.querySelector('select[name="unit"]')?.value || 'g';
            const weightUnit = (unitSelect === 'gm' ? 'g' : unitSelect);
            const weightStr = (weightInput ? parseInt(weightInput, 10) : 100) + weightUnit;

            // Detect category from checked categories and name
            const checkedCatElements = Array.from(document.querySelectorAll('input[name="category_ids[]"]:checked'));
            const catNames = checkedCatElements.map(el => (el.closest('.form-check')?.textContent || '').toLowerCase()).join(' ');

            const isCleansing = catNames.includes('cleansing') || nameLower.includes('cleansing') || nameLower.includes('reetha') || nameLower.includes('shikakai');
            const isFacePack = catNames.includes('face') || catNames.includes('skin') || nameLower.includes('face') || nameLower.includes('multani') || nameLower.includes('rose') || nameLower.includes('skin');
            const isHairMask = catNames.includes('hair') || nameLower.includes('hair') || nameLower.includes('bhringraj') || nameLower.includes('brahmi') || nameLower.includes('hibiscus') || nameLower.includes('methi');
            const isCombo = nameLower.includes('combo') || nameLower.includes('pack of 2') || nameLower.includes('pack of 3') || nameLower.includes('kit') || nameLower.includes(' and ');

            // Module 1: Hero Banner
            const heroEyebrow = document.querySelector('input[name="a_plus_modules[hero_banner][eyebrow]"]');
            if (heroEyebrow && !heroEyebrow.value) {
                if (isCombo) heroEyebrow.value = 'Botanical Combination Pack';
                else if (isCleansing) heroEyebrow.value = 'Botanical Hair Cleansing';
                else if (isFacePack) heroEyebrow.value = 'Botanical Skin Care';
                else if (isHairMask) heroEyebrow.value = 'Botanical Hair Care';
                else heroEyebrow.value = 'Pure Botanical Powder';
            }

            const heroTitle = document.querySelector('input[name="a_plus_modules[hero_banner][title]"]');
            if (heroTitle && !heroTitle.value) {
                heroTitle.value = prodName;
            }

            const heroDesc = document.querySelector('textarea[name="a_plus_modules[hero_banner][description]"]');
            const shortDesc = document.querySelector('textarea[name="short_description"]')?.value;
            const benefitsVal = document.querySelector('textarea[name="benefits"]')?.value;
            if (heroDesc && !heroDesc.value) {
                if (shortDesc) {
                    heroDesc.value = shortDesc.replace(/<[^>]*>?/gm, '').trim().substring(0, 300);
                } else if (benefitsVal) {
                    heroDesc.value = benefitsVal.replace(/<[^>]*>?/gm, '').trim().substring(0, 300);
                } else {
                    heroDesc.value = 'Carefully processed pure botanical powder formulated for traditional personal care routines.';
                }
            }

            // Module 2: Value Pillars
            const featTitle = document.querySelector('input[name="a_plus_modules[features_grid][title]"]');
            if (featTitle && !featTitle.value) {
                featTitle.value = 'Botanical Standards & Product Attributes';
            }
            const featSub = document.querySelector('input[name="a_plus_modules[features_grid][subtitle]"]');
            if (featSub && !featSub.value) {
                featSub.value = 'Factual, transparent product attributes for mindful home care routines.';
            }

            const pillarTitles = ['Traditional Botanicals', 'Fresh Powder Format', 'Declared Net Weight', 'External Personal Care'];
            const pillarDescs = [
                'Formulated with dried plant botanicals traditionally utilized in hair and skincare routines.',
                'Clean, dry powder format allowing fresh custom preparation with water or oils right before use.',
                'Packaged in sealed ' + weightStr + ' packaging to preserve botanical freshness and purity.',
                'Formulated specifically for topical cosmetic application on hair, scalp, or skin.'
            ];
            for (let i = 0; i < 4; i++) {
                const pTitle = document.querySelector(`input[name="a_plus_modules[features_grid][items][${i}][title]"]`);
                if (pTitle && !pTitle.value) pTitle.value = pillarTitles[i];
                const pDesc = document.querySelector(`textarea[name="a_plus_modules[features_grid][items][${i}][description]"]`);
                if (pDesc && !pDesc.value) pDesc.value = pillarDescs[i];
            }

            // Module 3: Split Story
            const storyEyebrow = document.querySelector('input[name="a_plus_modules[split_story][subtitle]"]');
            if (storyEyebrow && !storyEyebrow.value) storyEyebrow.value = 'Traditional Botanical Heritage';

            const storyTitle = document.querySelector('input[name="a_plus_modules[split_story][title]"]');
            if (storyTitle && !storyTitle.value) storyTitle.value = 'Time-Honored Botanical Care';

            const storyContent = document.querySelector('textarea[name="a_plus_modules[split_story][content]"]');
            if (storyContent && !storyContent.value) {
                if (benefitsVal) {
                    storyContent.value = benefitsVal.replace(/<[^>]*>?/gm, '').trim().substring(0, 450);
                } else {
                    storyContent.value = 'Carefully selected botanical powders brought together to support your traditional beauty rituals with pure, plant-based preparation.';
                }
            }

            const storyBullets = document.querySelector('textarea[name="a_plus_modules[split_story][bullets_raw]"]');
            if (storyBullets && !storyBullets.value) {
                storyBullets.value = "Dry powder format allows fresh preparation without synthetic liquid preservatives\nTraditional plant ingredients suitable for DIY masks, pastes, and rinses\nIntended strictly for topical personal care and external cosmetic use";
            }

            // Module 4: Botanical Ingredients Spotlight
            const herbDict = [
                { key: 'amla', name: 'Amla (Indian Gooseberry)', botanical: 'Phyllanthus emblica', benefit: 'Rich in natural fruit acids and antioxidants, traditionally used to condition hair and refresh scalp.' },
                { key: 'reetha', name: 'Reetha (Soapnut)', botanical: 'Sapindus mukorossi', benefit: 'Natural saponin-rich botanical traditionally utilized as a gentle, foaming herbal hair and scalp cleanser.' },
                { key: 'shikakai', name: 'Shikakai', botanical: 'Acacia concinna', benefit: 'Traditional fruit pod powder renowned for gentle low-pH scalp cleansing and detangling care.' },
                { key: 'bhringraj', name: 'Bhringraj', botanical: 'Eclipta alba', benefit: 'Classical false daisy herb celebrated in traditional Ayurvedic routines for strengthening hair roots.' },
                { key: 'hibiscus', name: 'Hibiscus Flower', botanical: 'Hibiscus rosa-sinensis', benefit: 'Naturally conditioning petal powder rich in botanical mucilage that leaves hair silky and manageable.' },
                { key: 'multani', name: 'Multani Mitti (Fuller’s Earth)', botanical: 'Solum Fullonum', benefit: 'Naturally absorbent mineral clay traditionally used in deep-cleansing facial and scalp packs.' },
                { key: 'rose', name: 'Rose Petal', botanical: 'Rosa centifolia', benefit: 'Aromatic petal powder traditionally prized for cooling, toning, and softening delicate facial skin.' },
                { key: 'neem', name: 'Neem Leaf', botanical: 'Azadirachta indica', benefit: 'Clarifying and purifying botanical powder widely incorporated in home scalp and skin packs.' },
                { key: 'moringa', name: 'Moringa Leaf', botanical: 'Moringa oleifera', benefit: 'Botanical powder rich in plant nutrients, traditionally utilized for revitalizing hair and skin masks.' },
                { key: 'beetroot', name: 'Beetroot', botanical: 'Beta vulgaris', benefit: 'Naturally pigmented root botanical traditionally blended into DIY conditioning pastes.' },
                { key: 'methi', name: 'Fenugreek (Methi)', botanical: 'Trigonella foenum-graecum', benefit: 'Protein-rich seeds traditionally ground into rich, slip-enhancing botanical hair masks.' },
                { key: 'brahmi', name: 'Brahmi', botanical: 'Bacopa monnieri', benefit: 'Time-honored rejuvenating botanical traditionally used in hair packs to soothe and nourish the scalp.' }
            ];

            const ingText = (document.querySelector('textarea[name="ingredients"]')?.value || '') + ' ' + prodName;
            const ingTextLower = ingText.toLowerCase();
            const matchedHerbs = herbDict.filter(h => ingTextLower.includes(h.key)).slice(0, 3);

            matchedHerbs.forEach((herb, idx) => {
                const nameInput = document.querySelector(`input[name="a_plus_modules[ingredients_spotlight][items][${idx}][name]"]`);
                if (nameInput && !nameInput.value) nameInput.value = herb.name;
                const botInput = document.querySelector(`input[name="a_plus_modules[ingredients_spotlight][items][${idx}][botanical]"]`);
                if (botInput && !botInput.value) botInput.value = herb.botanical;
                const benInput = document.querySelector(`textarea[name="a_plus_modules[ingredients_spotlight][items][${idx}][benefit]"]`);
                if (benInput && !benInput.value) benInput.value = herb.benefit;
            });

            // Module 5: Three-Step Usage Ritual (Category-Aware)
            const ritualTitle = document.querySelector('input[name="a_plus_modules[how_to_use_ritual][title]"]');
            const ritualTip = document.querySelector('input[name="a_plus_modules[how_to_use_ritual][tip]"]');
            let stepsData = [];

            if (isCleansing) {
                if (ritualTitle && !ritualTitle.value) ritualTitle.value = 'Three-Step Hair Cleansing Ritual';
                if (ritualTip && !ritualTip.value) ritualTip.value = 'Botanical cleansing powders produce a gentle natural cleansing emulsion rather than chemical foam. Focus on scalp massage and thorough rinsing.';
                stepsData = [
                    { title: 'Mix with Warm Water', desc: 'Measure the required powder into a bowl and gradually stir in lukewarm water to create a smooth, fluid paste.' },
                    { title: 'Massage into Wet Scalp', desc: 'Apply gently onto wet scalp and hair lengths, massaging in gentle circular motions without harsh scrubbing.' },
                    { title: 'Rinse Thoroughly', desc: 'Rinse out completely with running water until the rinse water runs clear. Avoid contact with eyes.' }
                ];
            } else if (isFacePack) {
                if (ritualTitle && !ritualTitle.value) ritualTitle.value = 'Three-Step Face Pack Ritual';
                if (ritualTip && !ritualTip.value) ritualTip.value = 'Always perform a 24-hour patch test behind your ear before initial facial use. Do not allow clay or botanical packs to dry to cracking stage.';
                stepsData = [
                    { title: 'Measure & Blend', desc: 'Take the required powder in a clean bowl. Blend with clean water or rose water to achieve a smooth, lump-free paste.' },
                    { title: 'Apply Evenly', desc: 'Gently smooth an even layer over face and neck, keeping completely clear of the delicate eye and lip contours.' },
                    { title: 'Rinse Before Dry', desc: 'Rinse off gently with lukewarm water before the mask dries completely. Pat dry and follow with your regular moisturizer.' }
                ];
            } else if (isHairMask) {
                if (ritualTitle && !ritualTitle.value) ritualTitle.value = 'Three-Step Hair Mask Ritual';
                if (ritualTip && !ritualTip.value) ritualTip.value = 'Conduct a patch test prior to first use. Covering hair with a damp towel or shower cap helps keep the paste moist during application.';
                stepsData = [
                    { title: 'Prepare Smooth Paste', desc: 'Mix the desired amount of powder with warm water, curd, or aloe vera gel in a clean bowl until smooth.' },
                    { title: 'Coat Roots to Ends', desc: 'Part damp hair in sections and apply the paste evenly from scalp and roots through to the tips.' },
                    { title: 'Rinse Thoroughly', desc: 'Leave on for the duration indicated on product packaging, then rinse out thoroughly with plain water.' }
                ];
            } else if (isCombo) {
                if (ritualTitle && !ritualTitle.value) ritualTitle.value = 'Three-Step Dual Powder Ritual';
                if (ritualTip && !ritualTip.value) ritualTip.value = 'Always conduct a patch test prior to combining botanical powders for the first time. For external cosmetic use only.';
                stepsData = [
                    { title: 'Review Individual Pouches', desc: 'Check packaging directions for each individual pouch before mixing, ensuring ingredients suit your intended application.' },
                    { title: 'Measure & Blend', desc: 'Measure the powders as instructed by packaging and blend gradually with water to form a smooth paste.' },
                    { title: 'Apply & Rinse', desc: 'Apply to targeted hair or skin areas as directed on the packaging, then rinse thoroughly with running water.' }
                ];
            } else {
                if (ritualTitle && !ritualTitle.value) ritualTitle.value = 'Three-Step Application Ritual';
                if (ritualTip && !ritualTip.value) ritualTip.value = 'Always conduct a patch test on a small area of skin 24 hours prior to full application. For external cosmetic use only.';
                stepsData = [
                    { title: 'Check Label Directions', desc: 'Review package instructions for recommended quantity and suitable mixing liquid for your chosen routine.' },
                    { title: 'Mix Fresh Paste', desc: 'Blend the dry powder gradually with clean water in a non-metallic bowl until smooth and lump-free.' },
                    { title: 'Apply & Rinse', desc: 'Apply smoothly to target hair or skin areas as directed on packaging, then rinse thoroughly with water.' }
                ];
            }

            stepsData.forEach((st, sIdx) => {
                const sTitle = document.querySelector(`input[name="a_plus_modules[how_to_use_ritual][steps][${sIdx}][title]"]`);
                if (sTitle && !sTitle.value) sTitle.value = st.title;
                const sDesc = document.querySelector(`textarea[name="a_plus_modules[how_to_use_ritual][steps][${sIdx}][desc]"]`);
                if (sDesc && !sDesc.value) sDesc.value = st.desc;
            });

            // Module 6: Trust Section
            const statNumbers = [weightStr, 'Botanical', 'Cosmetic'];
            const statLabels = ['Declared Net Weight', 'Plant-Based Powder', 'External Use Only'];
            const statSubs = ['Net contents as declared on packaging', 'Traditional personal care botanicals', 'Formulated for topical hair & skin care'];

            for (let m = 0; m < 3; m++) {
                const numInput = document.querySelector(`input[name="a_plus_modules[clinical_stats][stats][${m}][number]"]`);
                if (numInput && !numInput.value) numInput.value = statNumbers[m];
                const lblInput = document.querySelector(`input[name="a_plus_modules[clinical_stats][stats][${m}][label]"]`);
                if (lblInput && !lblInput.value) lblInput.value = statLabels[m];
                const subInput = document.querySelector(`input[name="a_plus_modules[clinical_stats][stats][${m}][sub]"]`);
                if (subInput && !subInput.value) subInput.value = statSubs[m];
            }

            // Enable switch and expand Module 1
            const switchEl = document.getElementById('hasAPlusSwitch');
            if (switchEl) {
                switchEl.checked = true;
            }

            const heroCol = document.getElementById('collapseModHero');
            if (heroCol && typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(heroCol);
                bsCollapse.show();
            }
        });
    }
});
</script>
@endpush
