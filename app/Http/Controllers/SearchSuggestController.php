<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([
                'query' => $q,
                'products' => [],
                'categories' => [],
            ]);
        }

        $cleanQ = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $q);
        $term = trim($cleanQ !== '' ? $cleanQ : $q);

        // Matching products - aligned with SearchService (name, sku, short_description, search_keywords, category)
        $products = Product::query()
            ->vanriti()
            ->active()
            ->where(function ($query) use ($term, $q) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('search_keywords', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%")
                    ->orWhereHas('categories', function ($catQuery) use ($term, $q) {
                        $catQuery->where('name', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$q}%");
                    });

                // Support multi-word queries (e.g. "amla hair", "rose powder")
                $words = array_filter(explode(' ', $term), fn ($w) => mb_strlen($w) >= 2);
                if (count($words) > 1) {
                    $query->orWhere(function ($multi) use ($words) {
                        foreach ($words as $word) {
                            $multi->where(function ($wQ) use ($word) {
                                $wQ->where('name', 'like', "%{$word}%")
                                    ->orWhere('search_keywords', 'like', "%{$word}%")
                                    ->orWhere('short_description', 'like', "%{$word}%")
                                    ->orWhereHas('categories', fn ($c) => $c->where('name', 'like', "%{$word}%"));
                            });
                        }
                    });
                }
            })
            ->with(['images', 'categories', 'variants'])
            ->take(8)
            ->get()
            ->map(function (Product $product) {
                $primaryImage = $product->getPrimaryImage()?->image_path;
                $imageUrl = $primaryImage ? image_url($primaryImage) : null;
                $category = $product->categories->first();

                $inStock = $product->hasStock();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'url' => route('product.show', $product->slug),
                    'price' => format_price($product->selling_price),
                    'mrp' => $product->mrp > $product->selling_price ? format_price($product->mrp) : null,
                    'image' => $imageUrl,
                    'category' => $category?->name,
                    'in_stock' => $inStock,
                ];
            });

        // Matching categories (Using scopeActive() which checks status='active')
        $categories = Category::query()
            ->active()
            ->where(function ($catQuery) use ($term, $q) {
                $catQuery->where('name', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$q}%");
            })
            ->take(4)
            ->get(['id', 'name', 'slug'])
            ->map(function (Category $cat) {
                return [
                    'name' => $cat->name,
                    'url' => route('shop.category', $cat->slug),
                ];
            });

        return response()->json([
            'query' => $q,
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
