<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;

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

        // 1. Tagged Products: Single unified collection for Bestsellers, Featured, & New Arrivals
        $taggedProducts = Product::query()
            ->vanriti()
            ->active()
            ->inStock()
            ->where(function ($q) {
                $q->where('is_bestseller', true)
                    ->orWhere('is_featured', true)
                    ->orWhere('is_new_arrival', true);
            })
            ->with(['images', 'categories', 'approvedReviews', 'activeVariants'])
            ->orderByDesc('is_bestseller')
            ->orderByDesc('is_featured')
            ->orderByDesc('total_sold')
            ->orderByDesc('id')
            ->limit(24)
            ->get();

        // Keep individual collections for backward compatibility with existing tests
        $featured = $taggedProducts->filter(fn ($p) => (bool) $p->is_featured)->values();
        $bestsellers = $taggedProducts->filter(fn ($p) => (bool) $p->is_bestseller)->values();
        $newArrivals = $taggedProducts->filter(fn ($p) => (bool) $p->is_new_arrival)->values();

        // 2. Category Image Showcase: 3 categories selected from admin (or fallback to top 3 active root)
        $showcaseCategories = Category::query()
            ->where('show_on_homepage', true)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->take(3)
            ->get();

        if ($showcaseCategories->isEmpty()) {
            $showcaseCategories = Category::query()
                ->whereNull('parent_id')
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->take(3)
                ->get();
        }

        // 3. Multi-Category Sections: Active root categories with in-stock products (including subcategories)
        $rootCategories = Category::query()
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $categorySections = $rootCategories->map(function ($cat) {
            $ids = $cat->descendants();
            $products = Product::query()
                ->vanriti()
                ->active()
                ->inStock()
                ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $ids)->active())
                ->with(['images', 'categories', 'approvedReviews', 'activeVariants'])
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            $cat->setRelation('products', $products);

            return $cat;
        })->filter(fn ($cat) => $cat->products->isNotEmpty())->values();

        $categories = $categorySections;

        // 4. Distinct Product Reviews Section: 20 approved reviews, distinct per product
        $homepageReviews = Review::query()
            ->where('status', 'approved')
            ->where('rating', '>=', 4)
            ->whereNotNull('comment')
            ->whereHas('product', fn ($q) => $q->vanriti()->active())
            ->with(['product.images'])
            ->latest('id')
            ->get()
            ->unique('product_id')
            ->take(20)
            ->values();

        // 5. Shoppable Instagram Reels: curated reels active on website
        $reels = \App\Models\InstagramReel::query()
            ->active()
            ->ordered()
            ->with(['product.images'])
            ->limit(12)
            ->get();

        // 6. Recent Editorial Journal Stories: 5 latest published articles
        $recentBlogs = \App\Models\Blog::query()
            ->published()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->limit(5)
            ->get();

        return view('storefront.home', compact(
            'banners',
            'sectionBanners',
            'taggedProducts',
            'featured',
            'bestsellers',
            'newArrivals',
            'showcaseCategories',
            'categorySections',
            'categories',
            'homepageReviews',
            'reels',
            'recentBlogs'
        ));
    }
}
