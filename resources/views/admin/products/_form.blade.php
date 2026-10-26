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
});
</script>
@endpush
