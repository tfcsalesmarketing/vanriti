<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class WishlistService
{
    protected const COOKIE_NAME = 'vanriti_wishlist';

    public function getWishlist(): ?Wishlist
    {
        if (auth()->check()) {
            return Wishlist::where('owner_type', User::class)
                ->where('owner_id', auth()->id())
                ->first();
        }

        $sessionId = request()->cookie(self::COOKIE_NAME);
        if (! $sessionId) {
            return null;
        }

        return Wishlist::where('owner_type', 'guest')->where('session_id', $sessionId)->first();
    }

    public function create(): Wishlist
    {
        if (auth()->check()) {
            return Wishlist::firstOrCreate([
                'owner_type' => User::class,
                'owner_id' => auth()->id(),
            ]);
        }

        $sessionId = request()->cookie(self::COOKIE_NAME) ?? 'wish_'.Str::random(32);
        Cookie::queue(Cookie::forever(self::COOKIE_NAME, $sessionId));

        return Wishlist::firstOrCreate(
            ['owner_type' => 'guest', 'session_id' => $sessionId],
            ['owner_id' => 0]
        );
    }

    public function toggle(Product $product): array
    {
        $wishlist = $this->getWishlist() ?? $this->create();

        $exists = $wishlist->items()->where('products.id', $product->id)->exists();

        if ($exists) {
            $wishlist->items()->detach($product->id);

            return ['added' => false, 'count' => $wishlist->items()->count()];
        }

        $wishlist->items()->attach($product->id);

        return ['added' => true, 'count' => $wishlist->items()->count()];
    }

    public function add(Product $product): void
    {
        $wishlist = $this->getWishlist() ?? $this->create();

        if (! $wishlist->items()->where('products.id', $product->id)->exists()) {
            $wishlist->items()->attach($product->id);
        }
    }

    public function remove(Product $product): void
    {
        $wishlist = $this->getWishlist();
        $wishlist?->items()->detach($product->id);
    }

    public function count(): int
    {
        return (int) ($this->getWishlist()?->items()->count() ?? 0);
    }

    public function has(Product $product): bool
    {
        return (bool) ($this->getWishlist()?->items()->where('products.id', $product->id)->exists() ?? false);
    }

    public function mergeGuestIntoUser(User $user): void
    {
        $sessionId = request()->cookie(self::COOKIE_NAME);
        if (! $sessionId) {
            return;
        }

        $guestWishlist = Wishlist::where('owner_type', 'guest')->where('session_id', $sessionId)->first();
        if (! $guestWishlist) {
            return;
        }

        $userWishlist = Wishlist::firstOrCreate([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);

        foreach ($guestWishlist->items as $product) {
            if (! $userWishlist->items()->where('products.id', $product->id)->exists()) {
                $userWishlist->items()->attach($product->id);
            }
        }

        $guestWishlist->delete();
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }
}