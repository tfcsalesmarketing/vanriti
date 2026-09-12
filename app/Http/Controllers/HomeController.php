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

        return view('storefront.home', compact('banners', 'featured', 'bestsellers', 'newArrivals', 'categories'));
    }
}