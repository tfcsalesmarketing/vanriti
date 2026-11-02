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

        // 1. Featured Collection: strictly products with is_featured = true
        $featured = Product::query()
            ->active()
            ->featured()
            ->inStock()
            ->with(['images', 'categories', 'approvedReviews'])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $featuredIds = $featured->pluck('id');

        // 2. Bestsellers: strictly products with is_bestseller = true
        // Avoid duplicate products already presented in Featured above
        $bestsellersBase = Product::query()
            ->active()
            ->bestsellers()
            ->inStock()
            ->with(['images', 'categories', 'approvedReviews'])
            ->orderByDesc('total_sold')
            ->orderByDesc('id');

        $bestsellers = (clone $bestsellersBase)
            ->when($featuredIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $featuredIds))
            ->limit(8)
            ->get();

        if ($bestsellers->isEmpty()) {
            $bestsellers = $bestsellersBase->limit(8)->get();
        }

        $shownIds = $featuredIds->concat($bestsellers->pluck('id'))->unique();

        // 3. New Arrivals: strictly products with is_new_arrival = true
        // Avoid duplicate products already presented in Featured or Bestsellers
        $newArrivalsBase = Product::query()
            ->active()
            ->newArrivals()
            ->inStock()
            ->with(['images', 'categories', 'approvedReviews'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $newArrivals = (clone $newArrivalsBase)
            ->when($shownIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $shownIds))
            ->limit(8)
            ->get();

        if ($newArrivals->isEmpty()) {
            $newArrivals = $newArrivalsBase->limit(8)->get();
        }

        $categories = Category::query()
            ->with(['children'])
            ->whereNull('parent_id')
            ->active()
            ->orderBy('sort_order')
            ->get();

        // 4. Shoppable Instagram Reels: curated reels active on website
        $reels = \App\Models\InstagramReel::query()
            ->active()
            ->ordered()
            ->with(['product.images'])
            ->limit(12)
            ->get();

        return view('storefront.home', compact('banners', 'sectionBanners', 'featured', 'bestsellers', 'newArrivals', 'categories', 'reels'));
    }
}
