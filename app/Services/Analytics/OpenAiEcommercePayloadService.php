<?php

namespace App\Services\Analytics;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Transforms application models into official OpenAI Measurement Pixel and
 * Conversions API payloads according to the verified OpenAI Ads specification.
 *
 * Rules:
 * - Uses the standard 'contents' data shape for ecommerce events.
 * - Event-level `amount` is the entire event value in currency minor units (paise).
 * - Item-level `amount` in `contents[]` is the single-unit price in paise.
 * - Quantity is a separate integer.
 * - Only documented standard fields are included.
 */
class OpenAiEcommercePayloadService
{
    public const CURRENCY = 'INR';

    /**
     * Convert INR decimal value to minor units (paise).
     */
    public function toPaise(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Build `contents_viewed` payload for a PDP view.
     */
    public function contentsViewed(Product $product, ?ProductVariant $variant = null): array
    {
        $unitPrice = (float) ($variant?->selling_price ?? $product->selling_price);
        $amountPaise = $this->toPaise($unitPrice);
        $sku = (string) ($variant?->sku ?? $product->sku);

        return [
            'type' => 'contents',
            'amount' => $amountPaise,
            'currency' => self::CURRENCY,
            'contents' => [
                [
                    'id' => $sku,
                    'name' => (string) $product->name,
                    'quantity' => 1,
                    'amount' => $amountPaise,
                ],
            ],
        ];
    }

    /**
     * Build `items_added` payload for an Add-to-Cart event.
     */
    public function itemsAdded(Product $product, ?ProductVariant $variant, int $quantity): array
    {
        $quantity = max(1, $quantity);
        $unitPrice = (float) ($variant?->selling_price ?? $product->selling_price);
        $unitPaise = $this->toPaise($unitPrice);
        $totalPaise = $this->toPaise($unitPrice * $quantity);
        $sku = (string) ($variant?->sku ?? $product->sku);

        return [
            'type' => 'contents',
            'amount' => $totalPaise,
            'currency' => self::CURRENCY,
            'contents' => [
                [
                    'id' => $sku,
                    'name' => (string) $product->name,
                    'quantity' => $quantity,
                    'amount' => $unitPaise,
                ],
            ],
        ];
    }

    /**
     * Build `checkout_started` payload for a Checkout view.
     */
    public function checkoutStarted(Cart $cart): array
    {
        $cart->loadMissing(['items.product', 'items.variant']);

        $contents = [];
        $totalPaise = 0;

        foreach ($cart->items as $item) {
            $unitPaise = $this->toPaise((float) $item->unit_price);
            $qty = (int) $item->quantity;
            $linePaise = $this->toPaise((float) $item->unit_price * $qty);
            $totalPaise += $linePaise;

            $sku = (string) ($item->variant?->sku ?? $item->product?->sku ?? 'ITEM-'.$item->id);

            $contents[] = [
                'id' => $sku,
                'name' => (string) ($item->product?->name ?? 'Product'),
                'quantity' => $qty,
                'amount' => $unitPaise,
            ];
        }

        return [
            'type' => 'contents',
            'amount' => $totalPaise,
            'currency' => self::CURRENCY,
            'contents' => $contents,
        ];
    }

    /**
     * Build `order_created` payload for an Order conversion.
     */
    public function orderCreated(Order $order): array
    {
        $order->loadMissing(['items']);

        $contents = [];

        foreach ($order->items as $item) {
            if (! $item instanceof OrderItem) {
                continue;
            }

            $contents[] = [
                'id' => (string) $item->sku,
                'name' => (string) $item->product_name,
                'quantity' => (int) $item->quantity,
                'amount' => $this->toPaise((float) $item->unit_price),
            ];
        }

        return [
            'type' => 'contents',
            'amount' => $this->toPaise((float) $order->grand_total),
            'currency' => self::CURRENCY,
            'order_id' => (string) $order->order_number,
            'contents' => $contents,
        ];
    }
}

