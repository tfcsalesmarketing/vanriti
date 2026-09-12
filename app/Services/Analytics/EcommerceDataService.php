<?php

namespace App\Services\Analytics;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\ReturnItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * Transforms persisted Laravel application data into standardised ecommerce
 * analytics payloads (GA4 item conventions). This service is a pure data
 * transformer: it never sends requests, initialises tracking SDKs, or alters
 * any cart, order, payment or refund business logic.
 */
class EcommerceDataService
{
    protected const CURRENCY = 'INR';

    /**
     * Single product (optionally with a selected variant) -> GA4 item.
     *
     * When a variant exists and none is supplied, the default (or first active)
     * variant is used so the correct variant SKU/price is always reported.
     */
    public function item(Product $product, ?ProductVariant $variant = null, int $quantity = 1): array
    {
        $variant = $variant ?? $this->defaultVariant($product);

        $item = [
            'item_id' => (string) ($variant?->sku ?? $product->sku),
            'item_name' => (string) $product->name,
            'item_brand' => store_name(),
            'price' => (float) ($variant?->selling_price ?? $product->selling_price),
            'quantity' => max(1, $quantity),
        ];

        $this->applyCategory($item, $this->primaryCategory($product));

        if ($variant && $variant->name) {
            $item['item_variant'] = (string) $variant->name;
        }

        return $item;
    }

    /**
     * Single product (with its initial selected/default variant) -> GA4
     * view_item payload for a product detail page.
     */
    public function viewItem(Product $product, ?ProductVariant $variant = null, int $quantity = 1): array
    {
        $items = [$this->item($product, $variant, $quantity)];

        return [
            'event' => 'view_item',
            'ecommerce' => [
                'currency' => self::CURRENCY,
                'value' => $items[0]['price'],
                'items' => $items,
            ],
        ];
    }

    /**
     * Single product (with its initial selected/default variant) -> GA4
     * add_to_cart payload for a successful cart addition.
     *
     * quantity is the quantity added in this request, not the customer's
     * entire cart quantity. value is selling price x quantity.
     */
    public function addToCart(Product $product, ?ProductVariant $variant, int $quantity): array
    {
        $quantity = max(1, $quantity);

        $items = [$this->item($product, $variant, $quantity)];

        return [
            'event' => 'add_to_cart',
            'ecommerce' => [
                'currency' => self::CURRENCY,
                'value' => round($items[0]['price'] * $quantity, 2),
                'items' => $items,
            ],
        ];
    }

    /**
     * Persisted cart -> GA4 view_cart payload. value is the cart's merchandise
     * subtotal derived from the reported items (snapshot unit_price x quantity);
     * no shipping, tax re-addition, MRP or coupon logic is applied.
     */
    public function viewCart(Cart $cart): array
    {
        $items = $this->fromCart($cart);

        return [
            'event' => 'view_cart',
            'ecommerce' => [
                'currency' => self::CURRENCY,
                'value' => round(
                    collect($items)->sum(fn (array $item): float => (float) $item['price'] * (int) $item['quantity']),
                    2
                ),
                'items' => $items,
            ],
        ];
    }

    /**
     * Shared GA4 ecommerce core for the checkout funnel (currency, merchandise
     * value, items). Accepts the live cart or a persisted order so the COD
     * confirmation page can reuse the same contract after the cart is emptied.
     *
     * @param  Cart|Order  $source
     */
    public function checkoutEcommerce(Cart|Order $source): array
    {
        if ($source instanceof Order) {
            $source->loadMissing(['items']);

            $items = $source->items
                ->map(fn (OrderItem $item) => $this->orderItem($item))
                ->values()
                ->all();
        } else {
            $items = $this->fromCart($source);
        }

        return [
            'currency' => self::CURRENCY,
            'value' => round(
                collect($items)->sum(fn (array $item): float => (float) $item['price'] * (int) $item['quantity']),
                2
            ),
            'items' => $items,
        ];
    }

    /**
     * Persisted cart (with items) -> GA4 begin_checkout payload for a
     * checkout-page render. value is the merchandise/cart value.
     */
    public function beginCheckout(Cart $cart): array
    {
        return [
            'event' => 'begin_checkout',
            'ecommerce' => $this->checkoutEcommerce($cart),
        ];
    }

    /**
     * Shipping information entry -> GA4 add_shipping_info payload. shipping_tier
     * is only included when an actual application shipping method exists
     * (standard|express); no address fields are ever sent.
     */
    public function addShippingInfo(Cart|Order $source, ?string $shippingTier = null): array
    {
        $ecommerce = $this->checkoutEcommerce($source);

        if ($shippingTier !== null && $shippingTier !== '') {
            $ecommerce['shipping_tier'] = $shippingTier;
        }

        return [
            'event' => 'add_shipping_info',
            'ecommerce' => $ecommerce,
        ];
    }

    /**
     * Payment method selection -> GA4 add_payment_info payload. payment_type
     * preserves the application's canonical method values (cod|razorpay).
     */
    public function addPaymentInfo(Cart|Order $source, string $paymentType): array
    {
        $ecommerce = $this->checkoutEcommerce($source);
        $ecommerce['payment_type'] = $paymentType;

        return [
            'event' => 'add_payment_info',
            'ecommerce' => $ecommerce,
        ];
    }

    /**
     * Cart line item -> GA4 item, using the prices already resolved and stored
     * by the cart business logic (CartItem::unit_price snapshot).
     */
    public function cartItem(CartItem $item): array
    {
        $product = $item->product;
        $variant = $item->variant;

        $converted = [
            'item_id' => (string) ($variant?->sku ?? $product?->sku),
            'item_name' => (string) ($product?->name ?? ''),
            'item_brand' => store_name(),
            'price' => (float) $item->unit_price,
            'quantity' => (int) $item->quantity,
        ];

        if ($product) {
            $this->applyCategory($converted, $this->primaryCategory($product));
        }

        if ($variant?->name) {
            $converted['item_variant'] = (string) $variant->name;
        }

        return $converted;
    }

    /**
     * Collection of CartItem models -> GA4 items.
     *
     * @param  Collection<int, CartItem>  $items
     */
    public function cartItems(Collection $items): array
    {
        $items->load(['product.categories', 'variant']);

        return $items->map(fn (CartItem $item) => $this->cartItem($item))->all();
    }

    /**
     * Persisted cart -> GA4 items.
     */
    public function fromCart(Cart $cart): array
    {
        $cart->load(['items.product.categories', 'items.variant']);

        return $this->cartItems($cart->items);
    }

    /**
     * Persisted order line item -> GA4 item, built exclusively from the
     * OrderItem snapshot so later product changes never affect analytics.
     */
    public function orderItem(OrderItem $item): array
    {
        $converted = [
            'item_id' => (string) $item->sku,
            'item_name' => (string) $item->product_name,
            'item_brand' => store_name(),
            'price' => (float) $item->unit_price,
            'quantity' => (int) $item->quantity,
        ];

        if ($item->category_name) {
            $converted['item_category'] = (string) $item->category_name;
        }

        if ($item->variant_name) {
            $converted['item_variant'] = (string) $item->variant_name;
        }

        return $converted;
    }

    /**
     * Persisted order -> GA4 purchase payload.
     *
     * value is the customer's actual payable (grand_total), which already
     * includes embedded GST and shipping and deducts any coupon.
     */
    public function purchase(Order $order): array
    {
        $order->loadMissing(['items']);

        $ecommerce = [
            'transaction_id' => (string) $order->order_number,
            'value' => (float) $order->grand_total,
            'tax' => (float) $order->tax_amount,
            'shipping' => (float) $order->shipping_charge,
            'currency' => self::CURRENCY,
        ];

        if ($order->coupon_code) {
            $ecommerce['coupon'] = (string) $order->coupon_code;
        }

        $ecommerce['items'] = $order->items
            ->map(fn (OrderItem $item) => $this->orderItem($item))
            ->values()
            ->all();

        return [
            'event' => 'purchase',
            'ecommerce' => $ecommerce,
        ];
    }

    /**
     * Persisted refund -> GA4 refund payload keyed to the original order.
     *
     * Refunded items carry negative quantities (GA4 refund convention),
     * derived from the return request's returned items when available.
     */
    public function refund(Refund $refund): array
    {
        $refund->loadMissing(['order.items', 'returnRequest.items.orderItem']);

        $ecommerce = [
            'transaction_id' => $refund->order?->order_number,
            'value' => (float) $refund->amount,
            'currency' => self::CURRENCY,
        ];

        $items = $this->refundItems($refund);

        if ($items !== []) {
            $ecommerce['items'] = $items;
        }

        return [
            'event' => 'refund',
            'ecommerce' => $ecommerce,
        ];
    }

    /**
     * Whether a placed order counts as a purchase conversion for analytics.
     *
     * COD is a conversion at order creation; online payments only once the
     * server has verified and marked the payment as paid. Read-only.
     */
    public function purchaseEligible(Order $order): bool
    {
        if (in_array($order->order_status, ['cancelled', 'failed'], true)) {
            return false;
        }

        if (in_array($order->payment_status, ['refunded', 'partially_refunded'], true)) {
            return false;
        }

        return match ($order->payment_method) {
            'cod' => true,
            default => $order->payment_status === 'paid',
        };
    }

    protected function refundItems(Refund $refund): array
    {
        $returnItems = $refund->returnRequest?->items ?? collect();

        $lines = [];

        if ($returnItems->isNotEmpty()) {
            foreach ($returnItems as $returnItem) {
                if ($returnItem instanceof ReturnItem && $returnItem->orderItem) {
                    $lines[] = [$returnItem->orderItem, (int) $returnItem->quantity];
                }
            }
        } elseif ($refund->type === 'full' && $refund->order) {
            foreach ($refund->order->items as $item) {
                $lines[] = [$item, (int) $item->quantity];
            }
        }

        return array_map(function (array $line): array {
            $item = $this->orderItem($line[0]);
            $item['quantity'] = -1 * $line[1];

            return $item;
        }, $lines);
    }

    protected function primaryCategory(Product $product): ?Category
    {
        if ($product->relationLoaded('categories')) {
            return $product->categories->first(
                fn (Category $category) => (bool) ($category->pivot->is_primary ?? false)
            ) ?? $product->categories->first();
        }

        return $product->primaryCategory();
    }

    protected function defaultVariant(Product $product): ?ProductVariant
    {
        $variants = $product->relationLoaded('activeVariants')
            ? $product->activeVariants
            : $product->activeVariants()->get();

        return $variants->first(
            fn (ProductVariant $variant) => (bool) $variant->is_default
        ) ?? $variants->first();
    }

    protected function applyCategory(array &$item, ?Category $category): void
    {
        if (! $category) {
            return;
        }

        $item['item_category'] = (string) $category->name;

        if ($category->parent) {
            $item['item_category2'] = (string) $category->parent->name;
        }
    }
}