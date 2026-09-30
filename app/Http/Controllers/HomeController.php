<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $banners = Banner::query()
            ->where('type', 'hero')
            ->where('position', 'home_top')
            ->active()
            ->get();

        // One promotional banner per homepage section slot, keyed by position.
        // scopeActive() already orders by sort_order, so first() per group is
        // the lowest one; id breaks any sort_order tie deterministically.
        $sectionBanners = Banner::query()
            ->whereIn('type', Banner::PROMOTIONAL_TYPES)
            ->whereIn('position', array_keys(Banner::SECTION_POSITIONS))
            ->active()
            ->orderBy('id')
            ->get()
            ->groupBy('position')
            ->map->first();

        $featured = Product::query()
            ->active()
            ->featured()
            ->inStock()
            ->with(['images', 'categories', 'approvedReviews'])
            ->limit(8)
            ->get();

        $bestsellers = Product::query()
            ->active()
            ->bestsellers()
            ->with(['images', 'categories', 'approvedReviews'])
            ->limit(8)
            ->get();

        $newArrivals = Product::query()
            ->active()
            ->newArrivals()
            ->with(['images', 'categories', 'approvedReviews'])
            ->limit(8)
            ->get();

        $categories = Category::query()
            ->with(['children'])
            ->whereNull('parent_id')
            ->active()
            ->orderBy('sort_order')
            ->get();

        $bestsellers = $bestsellers->isEmpty() ? $featured : $bestsellers;
        $newArrivals = $newArrivals->isEmpty() ? $featured : $newArrivals;

        return view('storefront.home', compact('banners', 'sectionBanners', 'featured', 'bestsellers', 'newArrivals', 'categories'));
    }
}
