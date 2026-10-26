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
                'products' => [],
                'categories' => [],
            ]);
        }

        $cleanQ = preg_replace('/[^\p{L}\p{N}\s]/u', '', $q);

        // Matching products
        $products = Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($cleanQ) {
                $query->where('name', 'like', "%{$cleanQ}%")
                    ->orWhere('short_description', 'like', "%{$cleanQ}%")
                    ->orWhereHas('categories', function ($catQuery) use ($cleanQ) {
                        $catQuery->where('name', 'like', "%{$cleanQ}%");
                    });
            })
            ->with(['images', 'categories', 'variants'])
            ->take(6)
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

        // Matching categories
        $categories = Category::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$cleanQ}%")
            ->take(3)
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
