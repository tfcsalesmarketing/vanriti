@php
    $coupon = $coupon ?? null;
    $productIds = $productIds ?? [];
    $categoryIds = $categoryIds ?? [];
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Coupon Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control form-control-sm text-uppercase" value="{{ old('code', $coupon->code ?? '') }}" required>
                        <div class="form-text">Will be stored in uppercase.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Discount Type <span class="text-danger">*</span></label>
                        <select name="discount_type" class="form-select form-select-sm" required>
                            <option value="percentage" {{ old('discount_type', $coupon->discount_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                            <option value="fixed" {{ old('discount_type', $coupon->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>Fixed (Amount)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Discount Value <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="discount_value" class="form-control form-control-sm" value="{{ old('discount_value', $coupon->discount_value ?? '') }}" required min="0.01">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Min Cart Value</label>
                        <input type="number" step="0.01" name="min_cart_value" class="form-control form-control-sm" value="{{ old('min_cart_value', $coupon->min_cart_value ?? 0) }}" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Max Discount (for percentage)</label>
                        <input type="number" step="0.01" name="max_discount" class="form-control form-control-sm" value="{{ old('max_discount', $coupon->max_discount ?? '') }}" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Starts At</label>
                        <input type="date" name="starts_at" class="form-control form-control-sm" value="{{ old('starts_at', $coupon && $coupon->starts_at ? $coupon->starts_at->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Expires At</label>
                        <input type="date" name="expires_at" class="form-control form-control-sm" value="{{ old('expires_at', $coupon && $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Usage Limit (total)</label>
                        <input type="number" name="usage_limit" class="form-control form-control-sm" value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}" min="0">
                        <div class="form-text">Leave blank for unlimited.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Per Customer Limit</label>
                        <input type="number" name="per_customer_limit" class="form-control form-control-sm" value="{{ old('per_customer_limit', $coupon->per_customer_limit ?? 1) }}" min="0">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control form-control-sm" rows="3">{{ old('description', $coupon->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Restrictions</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Applicable Products</label>
                    <select name="product_ids[]" class="form-select form-select-sm" multiple size="6">
                        <option value="" disabled>Select products (optional)</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" {{ in_array($product->id, old('product_ids', $productIds)) ? 'selected' : '' }}>{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Hold Ctrl/Cmd to select multiple. Leave empty to apply to all products.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Applicable Categories</label>
                    <select name="category_ids[]" class="form-select form-select-sm" multiple size="6">
                        <option value="" disabled>Select categories (optional)</option>
                        @php
                            $roots = collect($categories)->where('parent_id', null);
                        @endphp
                        @foreach ($roots as $root)
                            <option value="{{ $root->id }}" {{ in_array($root->id, old('category_ids', $categoryIds)) ? 'selected' : '' }}>{{ $root->name }}</option>
                            @foreach (collect($categories)->where('parent_id', $root->id) as $child)
                                <option value="{{ $child->id }}" {{ in_array($child->id, old('category_ids', $categoryIds)) ? 'selected' : '' }}>&nbsp;&nbsp;— {{ $child->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    <div class="form-text">Hold Ctrl/Cmd to select multiple. Leave empty to apply to all categories.</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Settings</div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="first_order_only" value="1" id="coupFirstOrder" {{ old('first_order_only', $coupon->first_order_only ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="coupFirstOrder">First Order Only</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="coupActive" {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="coupActive">Active</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Save Coupon</button>
    <a href="{{ route('admin.coupons.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
</div>
