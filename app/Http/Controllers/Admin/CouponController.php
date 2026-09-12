<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function __construct(
        protected ActivityLogger $logger,
    ) {}

    public function index(): View
    {
        $coupons = Coupon::query()
            ->withCount('usages')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        $products = Product::orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('name')->get();

        return view('admin.coupons.create', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:coupons,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.01',
            'min_cart_value' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'usage_limit' => 'nullable|integer|min:0',
            'per_customer_limit' => 'nullable|integer|min:0',
            'first_order_only' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $validated['code'] = Str::upper($validated['code']);
        $validated['first_order_only'] = $request->boolean('first_order_only');
        $validated['is_active'] = $request->boolean('is_active');

        $productIds = $validated['product_ids'] ?? [];
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['product_ids'], $validated['category_ids']);

        $coupon = Coupon::create($validated);
        $coupon->products()->sync($productIds);
        $coupon->categories()->sync($categoryIds);

        $this->logger->couponCreated(auth('admin')->user(), $coupon);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        $coupon->load(['products', 'categories']);

        $products = Product::orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('name')->get();
        $productIds = $coupon->products->pluck('id')->toArray();
        $categoryIds = $coupon->categories->pluck('id')->toArray();

        return view('admin.coupons.edit', compact('coupon', 'products', 'categories', 'productIds', 'categoryIds'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:coupons,code,' . $coupon->id,
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.01',
            'min_cart_value' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'usage_limit' => 'nullable|integer|min:0',
            'per_customer_limit' => 'nullable|integer|min:0',
            'first_order_only' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $validated['code'] = Str::upper($validated['code']);
        $validated['first_order_only'] = $request->boolean('first_order_only');
        $validated['is_active'] = $request->boolean('is_active');

        $productIds = $validated['product_ids'] ?? [];
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['product_ids'], $validated['category_ids']);

        $coupon->update($validated);
        $coupon->products()->sync($productIds);
        $coupon->categories()->sync($categoryIds);

        $this->logger->log('coupon_updated', $coupon, 'Coupon ' . $coupon->code . ' updated.');

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $this->logger->log('coupon_deleted', $coupon, 'Coupon ' . $coupon->code . ' deleted.');

        $coupon->delete();

        return redirect()->back()->with('success', 'Coupon deleted.');
    }
}
