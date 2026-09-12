<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        Paginator::useBootstrap();

        $query = trim((string) $request->query('q', ''));
        $isFeatured = $request->boolean('is_featured') || $request->query('is_featured') === '1';

        if ($isFeatured) {
            $products = $this->featuredSearch($request);
        } else {
            $products = app(SearchService::class)->search($query, $this->filtersFromRequest($request), 12);
        }

        $categories = Category::query()
            ->with(['children'])
            ->whereNull('parent_id')
            ->active()
            ->orderBy('sort_order')
            ->get();

        return view('storefront.shop', [
            'products' => $products,
            'query' => $query,
            'categories' => $categories,
            'sort' => (string) $request->query('sort', ''),
            'isFeatured' => $isFeatured,
            'selectedCategories' => array_values(array_filter((array) $request->input('category'))),
            'minPrice' => $request->query('min_price', ''),
            'maxPrice' => $request->query('max_price', ''),
        ]);
    }

    public function category(Category $category)
    {
        Paginator::useBootstrap();

        $ids = $category->descendants();

        $products = Product::query()
            ->active()
            ->with(['categories', 'images', 'variants', 'activeVariants', 'approvedReviews'])
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $ids))
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('storefront.category', [
            'category' => $category,
            'products' => $products,
        ]);
    }

    protected function filtersFromRequest(Request $request): array
    {
        return [
            'category' => array_values(array_filter((array) $request->input('category'))),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'sort' => $request->query('sort', ''),
        ];
    }

    protected function featuredSearch(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $queryBuilder = Product::query()
            ->active()
            ->featured()
            ->with(['categories', 'images', 'variants', 'activeVariants', 'approvedReviews']);

        if ($query !== '') {
            $queryBuilder->where(function ($builder) use ($query) {
                $builder->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('sku', 'LIKE', "%{$query}%")
                    ->orWhere('short_description', 'LIKE', "%{$query}%")
                    ->orWhere('search_keywords', 'LIKE', "%{$query}%")
                    ->orWhereHas('categories', fn ($c) => $c->where('name', 'LIKE', "%{$query}%"));
            });
        }

        $category = array_values(array_filter((array) $request->input('category')));
        if (! empty($category)) {
            $queryBuilder->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $category));
        }

        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        if ($minPrice !== null || $maxPrice !== null) {
            $queryBuilder->whereBetween('selling_price', [
                $minPrice !== null && $minPrice !== '' ? (float) $minPrice : 0,
                $maxPrice !== null && $maxPrice !== '' ? (float) $maxPrice : 99999999,
            ]);
        }

        match ($request->query('sort', '')) {
            'price_asc' => $queryBuilder->orderBy('selling_price'),
            'price_desc' => $queryBuilder->orderByDesc('selling_price'),
            'newest' => $queryBuilder->orderByDesc('created_at'),
            'popular' => $queryBuilder->orderByDesc('total_sold'),
            default => $queryBuilder->orderByDesc('created_at'),
        };

        return $queryBuilder->paginate(12)->withQueryString();
    }
}