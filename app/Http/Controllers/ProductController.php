<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        if ($product->status !== 'active') {
            abort(404);
        }

        $product->load([
            'images',
            'activeVariants',
            'categories',
            'reviews' => fn ($q) => $q->where('status', 'approved')->orderByDesc('created_at'),
            'reviews.user',
        ]);

        $categoryIds = $product->categories->pluck('id');

        $related = $categoryIds->isNotEmpty()
            ? Product::query()
                ->active()
                ->where('id', '!=', $product->id)
                ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
                ->with(['images', 'categories', 'approvedReviews'])
                ->limit(4)
                ->get()
            : collect();

        return view('storefront.product', compact('product', 'related'));
    }
}