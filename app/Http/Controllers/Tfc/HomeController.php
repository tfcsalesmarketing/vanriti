<?php

namespace App\Http\Controllers\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tfc\TfcBanner;
use App\Models\Tfc\TfcBlog;
use App\Models\Tfc\TfcSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Homepage of TFC Sales & Marketing.
     */
    public function index(): View
    {
        $banners = TfcBanner::active()->where('position', 'hero')->get();
        
        // Synced live from VANRITI catalogue
        $featuredProducts = Product::active()
            ->with(['images', 'categories', 'variants'])
            ->where('is_featured', true)
            ->latest()
            ->take(8)
            ->get();

        // Fallback to latest products if featured count is small
        if ($featuredProducts->count() < 4) {
            $featuredProducts = Product::active()
                ->with(['images', 'categories', 'variants'])
                ->latest()
                ->take(8)
                ->get();
        }

        // Real products for the Hero Visual Showcase (active products with verified images)
        $heroProducts = Product::active()
            ->with(['images', 'categories'])
            ->whereHas('images')
            ->latest()
            ->take(3)
            ->get();

        // Active Root Product Categories with aggregated product counts
        $categories = Category::active()
            ->roots()
            ->with(['children'])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($cat) {
                $allIds = array_merge([$cat->id], $cat->children->pluck('id')->toArray());
                $cat->total_products_count = Product::active()->whereHas('categories', function ($q) use ($allIds) {
                    $q->whereIn('categories.id', $allIds);
                })->count();
                return $cat;
            });

        $latestBlogs = TfcBlog::published()
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('tfc.home', compact('banners', 'featuredProducts', 'heroProducts', 'categories', 'latestBlogs'));
    }

    /**
     * About TFC Sales & Marketing.
     */
    public function about(): View
    {
        return view('tfc.about');
    }

    /**
     * The VANRITI Brand Partnership & Quality Heritage.
     */
    public function brand(): View
    {
        $products = Product::active()
            ->with(['images'])
            ->take(6)
            ->get();

        return view('tfc.brand', compact('products'));
    }

    /**
     * Frequently Asked Questions.
     */
    public function faqs(): View
    {
        return view('tfc.faqs');
    }
}
