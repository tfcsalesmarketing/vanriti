<?php

namespace App\Http\Controllers\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Homepage of TFC Sales & Marketing.
     */
    public function index(): View
    {
        // Synced live from TFC products catalogue
        $featuredProducts = Product::tfc()
            ->active()
            ->with(['images', 'categories', 'variants'])
            ->where('is_featured', true)
            ->latest()
            ->take(8)
            ->get();

        if ($featuredProducts->count() < 4) {
            $featuredProducts = Product::tfc()
                ->active()
                ->with(['images', 'categories', 'variants'])
                ->latest()
                ->take(8)
                ->get();
        }

        // Active Root Product Categories having TFC products
        $categories = Category::active()
            ->whereHas('products', fn ($q) => $q->tfc()->active())
            ->roots()
            ->with(['children'])
            ->orderBy('sort_order')
            ->get();

        return view('tfc.home', compact('featuredProducts', 'categories'));
    }

    /**
     * About TFC Sales & Marketing.
     */
    public function about(): View
    {
        return view('tfc.about');
    }

    /**
     * Downloads Center: Business Presentation, Product Catalogue, Guidelines.
     */
    public function downloads(): View
    {
        return view('tfc.downloads');
    }

    /**
     * Frequently Asked Questions.
     */
    public function faqs(): View
    {
        return view('tfc.faqs');
    }
}
