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
            ->with(['categories', 'images', 'variants', 'activeVariants', 'approvedReviews']);

        if ($q !== '') {
            $queryBuilder->where(function ($builder) use ($q) {
                $builder->where('name', 'LIKE', "%{$q}%")
                    ->orWhere('sku', 'LIKE', "%{$q}%")
                    ->orWhere('short_description', 'LIKE', "%{$q}%")
                    ->orWhere('search_keywords', 'LIKE', "%{$q}%")
                    ->orWhereHas('categories', fn ($c) => $c->where('name', 'LIKE', "%{$q}%"));
            });
        }

        if (! empty($filters['is_featured'])) {
            $queryBuilder->featured();
        }

        if (! empty($filters['is_bestseller'])) {
            $queryBuilder->bestsellers();
        }

        if (! empty($filters['is_new_arrival'])) {
            $queryBuilder->newArrivals();
        }

        if (! empty($filters['category'])) {
            $queryBuilder->whereHas('categories', fn ($c) => $c->whereIn('categories.id', (array) $filters['category']));
        }

        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;
        if (($minPrice !== null && $minPrice !== '') || ($maxPrice !== null && $maxPrice !== '')) {
            $queryBuilder->whereBetween('selling_price', [
                $minPrice !== null && $minPrice !== '' ? (float) $minPrice : 0,
                $maxPrice !== null && $maxPrice !== '' ? (float) $maxPrice : 99999999,
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
            if (! empty($filters['is_bestseller'])) {
                $queryBuilder->orderByDesc('total_sold')->orderByDesc('id');
            } else {
                $queryBuilder->orderByDesc('created_at')->orderByDesc('id');
            }
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
