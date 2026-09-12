<?php

namespace App\Services;

use App\Models\Product;

class SearchService
{
    public function search(string $query, array $filters = [], int $perPage = 12)
    {
        $q = trim($query);

        $queryBuilder = Product::query()
            ->active()
            ->with(['categories', 'images', 'variants'])
            ->where(function ($builder) use ($q) {
                $builder->where('name', 'LIKE', "%{$q}%")
                    ->orWhere('sku', 'LIKE', "%{$q}%")
                    ->orWhere('short_description', 'LIKE', "%{$q}%")
                    ->orWhere('search_keywords', 'LIKE', "%{$q}%")
                    ->orWhereHas('categories', fn ($c) => $c->where('name', 'LIKE', "%{$q}%"));
            });

        if (! empty($filters['category'])) {
            $queryBuilder->whereHas('categories', fn ($c) => $c->whereIn('categories.id', (array) $filters['category']));
        }

        if (! empty($filters['min_price']) || ! empty($filters['max_price'])) {
            $queryBuilder->whereBetween('selling_price', [
                $filters['min_price'] ?? 0,
                $filters['max_price'] ?? 99999999,
            ]);
        }

        if (! empty($filters['sort'])) {
            match ($filters['sort']) {
                'price_asc' => $queryBuilder->orderBy('selling_price'),
                'price_desc' => $queryBuilder->orderByDesc('selling_price'),
                'newest' => $queryBuilder->orderByDesc('created_at'),
                'popular' => $queryBuilder->orderByDesc('total_sold'),
                default => $queryBuilder->orderByDesc('created_at'),
            };
        } else {
            $queryBuilder->orderByDesc('created_at');
        }

        return $queryBuilder->paginate($perPage)->withQueryString();
    }

    public function suggestions(string $query, int $limit = 8): array
    {
        $q = trim($query);

        if (mb_strlen($q) < 2) {
            return [];
        }

        return Product::query()
            ->active()
            ->inStock()
            ->with(['images'])
            ->where(function ($builder) use ($q) {
                $builder->where('name', 'LIKE', "%{$q}%")
                    ->orWhere('sku', 'LIKE', "%{$q}%")
                    ->orWhere('search_keywords', 'LIKE', "%{$q}%");
            })
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => [
                'slug' => $product->slug,
                'name' => $product->name,
                'price' => format_price($product->selling_price),
                'mrp' => $product->mrp ? format_price($product->mrp) : null,
                'image' => $product->getPrimaryImage()?->image_path,
            ])
            ->toArray();
    }
}