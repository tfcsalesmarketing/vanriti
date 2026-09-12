<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Dadi\DadiAttributionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class CartService
{
    public const COOKIE_NAME = 'vanriti_cart';

    protected const COOKIE_DAYS = 30;

    protected ?string $rememberedSession = null;

    public function getCart(bool $create = false): ?Cart
    {
        $user = auth()->user();

        if ($user instanceof User) {
            $query = Cart::where('owner_type', User::class)->where('owner_id', $user->id);
        } else {
            $sessionId = $this->sessionId();
            if (! $sessionId) {
                return $create ? $this->createGuestCart() : null;
            }
            $query = Cart::where('owner_type', 'guest')->where('session_id', $sessionId);
        }

        $cart = $query->first();

        if (! $cart && $create) {
            $cart = $this->createGuestCart();
        }

        return $cart;
    }

    public function createGuestCart(): Cart
    {
        $sessionId = $this->rememberSession();

        return Cart::firstOrCreate(
            ['owner_type' => 'guest', 'owner_id' => 0, 'session_id' => $sessionId],
            ['owner_type' => 'guest', 'owner_id' => 0, 'session_id' => $sessionId]
        );
    }

    protected function sessionId(): ?string
    {
        return request()->cookie(self::COOKIE_NAME) ?? $this->rememberedSession;
    }

    protected function rememberSession(): string
    {
        $sessionId = $this->sessionId() ?? 'cart_'.Str::random(32);

        $this->rememberedSession = $sessionId;

        Cookie::queue(Cookie::forever(self::COOKIE_NAME, $sessionId));

        return $sessionId;
    }

    public function add(Product $product, int $quantity = 1, ?ProductVariant $variant = null): CartItem
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }

        $this->validateStock($product, $variant, $quantity);

        $cart = auth()->check()
            ? $this->getCartForUserOrCreate()
            : $this->getCart(true);

        $price = $variant?->selling_price ?? $product->selling_price;
        $mrp = $variant?->mrp ?? $product->mrp;
        $gst = $variant?->gst_rate ?? $product->gst_rate;

        $existing = $cart->items()
            ->where('product_id', $product->id)
            ->when($variant, fn ($q) => $q->where('product_variant_id', $variant->id), fn ($q) => $q->whereNull('product_variant_id'))
            ->first();

        if ($existing) {
            $newQuantity = $existing->quantity + $quantity;
            $this->validateStock($product, $variant, $newQuantity);
            $existing->update([
                'quantity' => $newQuantity,
                'unit_price' => $price,
                'mrp' => $mrp,
            ]);

            return $existing;
        }

        return $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
            'unit_price' => $price,
            'mrp' => $mrp,
            'gst_rate' => $gst,
        ]);
    }

    public function updateQuantity(int $cartItemId, int $quantity): void
    {
        $item = $this->ownedItem($cartItemId);

        if ($quantity < 1) {
            $this->remove($cartItemId);

            return;
        }

        $this->validateStock($item->product, $item->variant, $quantity);

        $item->update(['quantity' => $quantity]);
    }

    public function remove(int $cartItemId): void
    {
        $this->ownedItem($cartItemId)?->delete();
    }

    public function clear(): void
    {
        if ($cart = $this->getCart()) {
            $cart->items()->delete();
        }
    }

    public function count(): int
    {
        return (int) ($this->getCart()?->count() ?? 0);
    }

    public function items(): Collection
    {
        return $this->getCart()?->items()->with(['product.images', 'variant'])->get() ?? collect();
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function subtotal(): float
    {
        $cart = $this->getCart();

        return $cart ? (float) $cart->items()->get()->sum(fn ($i) => $i->subtotal()) : 0.0;
    }

    public function mergeGuestCartIntoUser(User $user): void
    {
        $sessionId = $this->sessionId();
        if (! $sessionId) {
            return;
        }

        try {
            // Attribution backfill is keyed on the stable guest-cart key (and
            // the framework session id as a fallback for page-level events).
            app(DadiAttributionService::class)->reconcileSessionToUser(
                $sessionId,
                $user->id,
                request()->session()->getId(),
            );
        } catch (\Throwable) {
            // Attribution is additive and must never affect cart merging.
        }

        $guestCart = Cart::where('owner_type', 'guest')->where('session_id', $sessionId)->first();

        if (! $guestCart || ! $guestCart->items()->exists()) {
            return;
        }

        $userCart = Cart::firstOrCreate([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);

        foreach ($guestCart->items as $item) {
            $product = $item->product;
            if (! $product || $product->status !== 'active') {
                continue;
            }

            $variant = $item->product_variant_id
                ? ProductVariant::where('id', $item->product_variant_id)->where('product_id', $product->id)->first()
                : null;

            try {
                $this->add($product, $item->quantity, $variant);
            } catch (\InvalidArgumentException|\RuntimeException $e) {
                continue;
            }
        }

        $guestCart->items()->delete();
        $guestCart->delete();

        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    public function mergeUserCartIntoSession(User $user): void
    {
        $sessionId = $this->sessionId();
        if (! $sessionId) {
            return;
        }

        $guestCart = Cart::where('owner_type', 'guest')->where('session_id', $sessionId)->first();
        if (! $guestCart) {
            return;
        }

        $guestCart->update([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'session_id' => null,
        ]);

        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    protected function getCartForUserOrCreate(): Cart
    {
        $cart = Cart::where('owner_type', User::class)
            ->where('owner_id', auth()->id())
            ->first();

        return $cart ?? Cart::create([
            'owner_type' => User::class,
            'owner_id' => auth()->id(),
        ]);
    }

    protected function ownedItem(int $cartItemId): ?CartItem
    {
        $cart = $this->getCart();

        return $cart?->items()->with(['product', 'variant'])->find($cartItemId);
    }

    public function validateStock(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        if ($product->status !== 'active') {
            throw new \RuntimeException('This product is not available for purchase.');
        }

        $available = $variant
            ? (int) $variant->stock
            : ($product->variants()->exists() ? (int) $product->variants()->where('status', 'active')->sum('stock') : (int) $product->stock);

        if ($variant && $variant->status !== 'active') {
            throw new \RuntimeException('The selected option is no longer available.');
        }

        if ($available <= 0) {
            throw new \RuntimeException('This product is currently out of stock.');
        }

        if ($quantity > $available) {
            throw new \RuntimeException("Only {$available} unit(s) are available in stock.");
        }
    }

    public function refreshPrices(): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        foreach ($cart->items as $item) {
            $product = $item->product;
            if (! $product || $product->status !== 'active') {
                $item->delete();

                continue;
            }

            $variant = $item->product_variant_id
                ? ProductVariant::where('id', $item->product_variant_id)->where('product_id', $product->id)->first()
                : null;

            $item->update([
                'unit_price' => $variant?->selling_price ?? $product->selling_price,
                'mrp' => $variant?->mrp ?? $product->mrp,
                'gst_rate' => $variant?->gst_rate ?? $product->gst_rate,
            ]);

            $available = $variant ? $variant->stock : ($product->variants()->exists() ? $product->variants()->sum('stock') : $product->stock);
            if ($item->quantity > $available) {
                $item->update(['quantity' => max(1, (int) $available)]);
            }
        }
    }
}
