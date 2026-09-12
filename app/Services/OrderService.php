<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Dadi\DadiAttributionService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected CartService $cartService,
        protected CouponService $couponService,
        protected ShippingService $shippingService,
        protected InventoryService $inventoryService,
        protected GstService $gstService,
    ) {}

    /**
     * Place an order from a cart. All pricing is recomputed server-side.
     *
     * @param  array{
     *   billing: array,
     *   shipping: array,
     *   shipping_method: string,
     *   payment_method: string,
     *   coupon_code: ?string,
     *   notes: ?string
     * }  $orderData
     * @param  bool  $clearCart  Whether to delete the cart items once the order is committed.
     */
    public function placeOrder(User $user, Cart $cart, array $orderData, bool $clearCart = true): Order
    {
        $order = DB::transaction(function () use ($user, $cart, $orderData, $clearCart) {
            if (! $cart->items()->exists()) {
                throw new \RuntimeException('Your cart is empty.');
            }

            if (! $user->isActive()) {
                throw new \RuntimeException('Your account is restricted. Please contact support.');
            }

            $cart->load('items.product', 'items.variant');
            $this->cartService->refreshPrices();

            $subtotal = 0.0;
            $discountAmount = 0.0;
            $taxableAmount = 0.0;
            $taxAmount = 0.0;
            $rows = [];

            foreach ($cart->fresh()->items as $item) {
                $product = $item->product;
                if (! $product || $product->status !== 'active') {
                    throw new \RuntimeException("{$item->product_name} is no longer available.");
                }

                $variant = $item->product_variant_id
                    ? ProductVariant::where('id', $item->product_variant_id)->where('status', 'active')->first()
                    : null;

                $this->cartService->validateStock($product, $variant, $item->quantity);

                $mrp = (float) ($variant?->mrp ?? $product->mrp);
                $price = (float) ($variant?->selling_price ?? $product->selling_price);
                $gst = (float) ($variant?->gst_rate ?? $product->gst_rate);
                $qty = (int) $item->quantity;

                $lineTotal = round($price * $qty, 2);
                $lineDiscount = ($mrp - $price) * $qty;
                $lineTaxable = $this->gstService->taxableAmount($lineTotal, $gst);
                $lineTax = $this->gstService->taxAmount($lineTotal, $gst);

                $subtotal += $lineTotal;
                $discountAmount += $lineDiscount;
                $taxableAmount += $lineTaxable;
                $taxAmount += $lineTax;

                $rows[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'variant_name' => $variant?->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'image' => $product->getPrimaryImage()?->image_path,
                    'category_name' => $product->primaryCategory()?->name,
                    'quantity' => $qty,
                    'mrp' => $mrp,
                    'unit_price' => $price,
                    'discount' => round($lineDiscount, 2),
                    'gst_rate' => $gst,
                    'total_price' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);

            // Coupon
            $coupon = null;
            $couponDiscount = 0.0;
            if (! empty($orderData['coupon_code'])) {
                $result = $this->couponService->validate($orderData['coupon_code'], $cart, $user);
                if (! $result['valid']) {
                    throw new \RuntimeException($result['message']);
                }
                $coupon = $result['coupon'];
                $couponDiscount = (float) $result['discount'];
            }

            // Coupon is shared across items proportionally so the invoice
            // reconciles: taxable + GST for each line equals its discounted total.
            if ($subtotal > 0 && $couponDiscount > 0) {
                $factor = max(0.0, ($subtotal - $couponDiscount) / $subtotal);

                foreach ($rows as &$row) {
                    $row['net'] = round($row['total_price'] * $factor, 2);
                }
                unset($row);

                $reducedSubtotal = round(array_sum(array_column($rows, 'net')), 2);
                $drift = round((float) $subtotal - $couponDiscount - $reducedSubtotal, 2);

                if ($drift != 0.0 && $rows) {
                    $largest = collect($rows)->sortByDesc('total_price')->keys()->first();
                    $rows[$largest]['net'] = round($rows[$largest]['net'] + $drift, 2);
                }
            } else {
                foreach ($rows as &$row) {
                    $row['net'] = $row['total_price'];
                }
                unset($row);
            }

            $taxableAmount = 0.0;
            $taxAmount = 0.0;
            foreach ($rows as &$row) {
                $row['taxable_amount'] = $this->gstService->taxableAmount($row['net'], $row['gst_rate']);
                $row['tax_amount'] = $this->gstService->taxAmount($row['net'], $row['gst_rate']);
                $taxableAmount += $row['taxable_amount'];
                $taxAmount += $row['tax_amount'];
            }
            unset($row);

            $taxableAmount = round($taxableAmount, 2);
            $taxAmount = round($taxAmount, 2);

            // Shipping
            $shippingAddress = new Address($orderData['shipping']);
            $shipping = $this->shippingService->calculate($subtotal, $shippingAddress, $orderData['shipping_method'] ?? 'standard');

            // Selling prices are GST-inclusive: the payable total is exactly
            // what the customer saw (no tax added on top).
            $grandTotal = round(max(0, $subtotal + $shipping['charge'] - $couponDiscount), 2);

            $minOrder = (float) (setting('min_order_amount', 1) ?: 1);
            if ($subtotal < $minOrder) {
                throw new \RuntimeException('Minimum order value is '.format_price($minOrder).'.');
            }

            $intraState = $this->gstService->isIntraState(
                $orderData['shipping']['state'] ?? ($orderData['billing']['state'] ?? '')
            );
            $taxSplit = $this->gstService->splitTax($taxAmount, $intraState);

            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon?->id,
                'order_number' => 'TEMP',
                ...$this->mapAddress('billing', $orderData['billing']),
                ...$this->mapAddress('shipping', $orderData['shipping']),
                'is_billing_same' => $this->addressesEqual($orderData['billing'], $orderData['shipping']),
                'subtotal' => $subtotal,
                'discount_amount' => round($discountAmount, 2),
                'coupon_discount' => $couponDiscount,
                'shipping_charge' => $shipping['charge'],
                'taxable_amount' => $taxableAmount,
                'tax_amount' => $taxAmount,
                'cgst_amount' => $taxSplit['cgst'],
                'sgst_amount' => $taxSplit['sgst'],
                'igst_amount' => $taxSplit['igst'],
                'coupon_code' => $coupon ? strtoupper($coupon->code) : null,
                'coupon_type' => $coupon?->discount_type,
                'coupon_value' => $coupon ? (float) $coupon->discount_value : null,
                'grand_total' => $grandTotal,
                'amount_due' => $grandTotal,
                'amount_paid' => 0,
                'payment_method' => $orderData['payment_method'],
                'payment_status' => $this->initialPaymentStatus($orderData['payment_method']),
                'order_status' => 'pending',
                'internal_notes' => $orderData['notes'] ?? null,
            ]);

            $order->update(['order_number' => generate_order_number($order->id)]);

            foreach ($rows as $row) {
                $row = Arr::except($row, 'net');

                OrderItem::create([...$row, 'order_id' => $order->id]);

                $stockable = $row['product_variant_id']
                    ? ProductVariant::find($row['product_variant_id'])
                    : Product::find($row['product_id']);

                if ($stockable) {
                    $this->inventoryService->withReference(
                        'sale',
                        $stockable,
                        $order,
                        -$row['quantity'],
                        "Order {$order->order_number}"
                    );
                }
            }

            $this->recordStatus($order, 'pending', 'Order placed successfully.');

            if ($coupon) {
                $this->couponService->recordUsage($coupon, $user, $order->id, $couponDiscount);
            }

            if ($clearCart) {
                $cart->items()->delete();
            }

            return $order->fresh();
        });

        // The purchase-attribution hook runs AFTER the order transaction is
        // committed: a best-effort analytics failure must never roll back or
        // poison the order write.
        try {
            app(DadiAttributionService::class)->recordPurchaseForOrder($order);
        } catch (\Throwable) {
            // Best-effort; must never affect the order outcome.
        }

        return $order;
    }

    public function updateOrderStatus(Order $order, string $newStatus, ?string $description = null): Order
    {
        $allowed = [
            'confirmed', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'failed',
        ];

        if (! in_array($newStatus, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid order status: {$newStatus}");
        }

        if ($newStatus !== 'cancelled' && in_array($order->order_status, ['cancelled', 'failed'], true)) {
            throw new \RuntimeException('This order cannot be updated.');
        }

        $oldStatus = $order->order_status;
        $order->update([
            'order_status' => $newStatus,
            'cancelled_at' => $newStatus === 'cancelled' ? now() : $order->cancelled_at,
        ]);

        $this->recordStatus($order, $newStatus, $description, $oldStatus);

        return $order;
    }

    public function recordStatus(Order $order, string $status, ?string $description = null, ?string $oldStatus = null): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status,
            'old_status' => $oldStatus,
            'description' => $description,
            'admin_id' => auth('admin')->id(),
        ]);
    }

    public function cancelOrder(Order $order, ?string $reason = null): void
    {
        if (! $order->isCancellable()) {
            throw new \RuntimeException('This order can no longer be cancelled.');
        }

        DB::transaction(function () use ($order, $reason) {
            $this->updateOrderStatus($order, 'cancelled', $reason ?? 'Cancelled by customer');

            $order->update(['cancellation_reason' => $reason]);

            foreach ($order->items as $item) {
                $stockable = $item->product_variant_id
                    ? ProductVariant::find($item->product_variant_id)
                    : Product::find($item->product_id);

                if ($stockable) {
                    $this->inventoryService->withReference(
                        'reversal',
                        $stockable,
                        $order,
                        $item->quantity,
                        "Stock restored for cancelled order {$order->order_number}"
                    );
                }
            }
        });
    }

    protected function mapAddress(string $prefix, array $data): array
    {
        return [
            $prefix.'_name' => $data['full_name'] ?? ($data['name'] ?? null),
            $prefix.'_mobile' => $data['mobile'] ?? null,
            $prefix.'_address_line1' => $data['address_line1'] ?? null,
            $prefix.'_address_line2' => $data['address_line2'] ?? null,
            $prefix.'_landmark' => $data['landmark'] ?? null,
            $prefix.'_city' => $data['city'] ?? null,
            $prefix.'_state' => $data['state'] ?? null,
            $prefix.'_pincode' => $data['pincode'] ?? null,
            $prefix.'_country' => $data['country'] ?? 'India',
        ];
    }

    protected function addressesEqual(array $billing, array $shipping): bool
    {
        $keys = ['full_name', 'name', 'mobile', 'address_line1', 'address_line2', 'landmark', 'city', 'state', 'pincode'];

        foreach ($keys as $key) {
            if (($billing[$key] ?? null) !== ($shipping[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    protected function initialPaymentStatus(string $method): string
    {
        return $method === 'cod' ? 'pending' : 'pending';
    }
}
