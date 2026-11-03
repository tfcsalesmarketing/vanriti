<?php

namespace App\Http\Controllers\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display products from TFC catalogue.
     */
    public function index(Request $request): View
    {
        $query = Product::tfc()->active()->with(['images', 'categories', 'variants']);

        // Optional category filter
        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.slug', $request->query('category'));
            });
        }

        // Search term
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->query('sort', 'latest');
        match ($sort) {
            'price_low' => $query->orderBy('selling_price', 'asc'),
            'price_high' => $query->orderBy('selling_price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::active()
            ->whereHas('products', fn ($q) => $q->tfc()->active())
            ->withCount(['products' => fn ($q) => $q->tfc()->active()])
            ->get();

        return view('tfc.products.index', compact('products', 'categories', 'sort'));
    }

    /**
     * Display products belonging to a specific category.
     */
    public function category(string $slug): View
    {
        $category = Category::active()->where('slug', $slug)->firstOrFail();

        $categoryIds = [$category->id];
        $childIds = $category->children()->pluck('id')->toArray();
        if (!empty($childIds)) {
            $categoryIds = array_merge($categoryIds, $childIds);
        }

        $products = Product::tfc()
            ->active()
            ->with(['images', 'categories', 'variants'])
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })
            ->latest()
            ->paginate(12);

        $categories = Category::active()
            ->whereHas('products', fn ($q) => $q->tfc()->active())
            ->withCount(['products' => fn ($q) => $q->tfc()->active()])
            ->get();

        return view('tfc.products.category', compact('category', 'products', 'categories'));
    }

    /**
     * Display single product details with live stock for TFC Sales & Marketing.
     */
    public function show(string $slug): View
    {
        $product = Product::tfc()
            ->active()
            ->with(['images', 'categories', 'variants', 'approvedReviews'])
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedProducts = Product::tfc()
            ->active()
            ->with(['images', 'categories', 'variants'])
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($q) use ($product) {
                $q->whereIn('categories.id', $product->categories->pluck('id'));
            })
            ->take(4)
            ->get();

        return view('tfc.products.show', compact('product', 'relatedProducts'));
    }
}
