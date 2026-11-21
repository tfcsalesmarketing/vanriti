<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Admin;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Dadi\DadiAttributionService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
                // Row-lock the catalogue rows so concurrent orders cannot both
                // pass the stock check for the last remaining unit (prevents
                // overselling when two checkouts race).
                $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->first();

                if (! $product || $product->status !== 'active') {
                    throw new \RuntimeException("{$item->product_name} is no longer available.");
                }

                $variant = $item->product_variant_id
                    ? ProductVariant::query()->where('id', $item->product_variant_id)->where('status', 'active')->lockForUpdate()->first()
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
                $result = $this->couponService->validate($orderData['coupon_code'], $cart, $user, lock: true);
                if (! $result['valid']) {
                    throw new \RuntimeException($result['message']);
                }
                $coupon = $result['coupon'];
                $couponDiscount = (float) $result['discount'];
            }

            // Prepaid Discount (Instant 2% or configured percentage for online/prepaid payments)
            $prepaidDiscountEnabled = (bool) setting('prepaid_discount_enabled', true);
            $prepaidPercentage = $prepaidDiscountEnabled ? max(0.0, (float) setting('prepaid_discount_percentage', 2.0)) : 0.0;
            $isPrepaid = in_array($orderData['payment_method'] ?? '', ['razorpay', 'upi', 'prepaid'], true);

            $prepaidDiscount = 0.0;
            if ($isPrepaid && $prepaidPercentage > 0 && $subtotal > 0) {
                // Additional 2% discount on the order subtotal before coupon discount
                $prepaidDiscount = round(($subtotal * $prepaidPercentage) / 100, 2);
            }

            $totalOrderDiscount = round($couponDiscount + $prepaidDiscount, 2);
            $totalOrderDiscount = min($subtotal, $totalOrderDiscount);

            // Discounts (coupon + prepaid) are shared across items proportionally so the invoice
            // reconciles: taxable + GST for each line equals its discounted total.
            if ($subtotal > 0 && $totalOrderDiscount > 0) {
                $factor = max(0.0, ($subtotal - $totalOrderDiscount) / $subtotal);

                foreach ($rows as &$row) {
                    $row['net'] = round($row['total_price'] * $factor, 2);
                }
                unset($row);

                $reducedSubtotal = round(array_sum(array_column($rows, 'net')), 2);
                $drift = round((float) $subtotal - $totalOrderDiscount - $reducedSubtotal, 2);

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
            $shippingCharge = array_key_exists('shipping_charge', $orderData)
                ? (float) $orderData['shipping_charge']
                : (float) $shipping['charge'];

            // Selling prices are GST-inclusive: the payable total is exactly
            // what the customer saw (no tax added on top).
            $grandTotal = round(max(0, $subtotal + $shippingCharge - $couponDiscount - $prepaidDiscount), 2);

            $minOrder = (float) (setting('min_order_amount', 1) ?: 1);
            if ($subtotal < $minOrder) {
                throw new \RuntimeException('Minimum order value is '.format_price($minOrder).'.');
            }

            $intraState = $this->gstService->isIntraState(
                $orderData['shipping']['state'] ?? ($orderData['billing']['state'] ?? '')
            );
            $taxSplit = $this->gstService->splitTax($taxAmount, $intraState);

            $attribution = app(\App\Services\Analytics\ChatGptAdsAttributionService::class)->resolve();

            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon?->id,
                // A literal 'TEMP' placeholder is unsafe: order_number carries a
                // UNIQUE index, so two concurrent checkouts would collide on the
                // placeholder itself and the loser saw a 500. The placeholder is
                // unique per attempt; the final number is derived from the freshly
                // inserted primary key and is therefore unique by construction.
                'order_number' => 'TMP-'.Str::upper(Str::random(16)),
                ...$this->mapAddress('billing', $orderData['billing']),
                ...$this->mapAddress('shipping', $orderData['shipping']),
                'is_billing_same' => $this->addressesEqual($orderData['billing'], $orderData['shipping']),
                'subtotal' => $subtotal,
                'discount_amount' => round($discountAmount, 2),
                'coupon_discount' => $couponDiscount,
                'prepaid_discount' => $prepaidDiscount,
                'shipping_charge' => $shippingCharge,
                'shipping_method' => $orderData['shipping_method'] ?? 'standard',
                'checkout_source' => $orderData['checkout_source'] ?? 'web',
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
                'oppref' => $orderData['oppref'] ?? ($attribution['oppref'] ?? null),
                'attribution_source' => $orderData['attribution_source'] ?? ($attribution['source'] ?? null),
                'attribution_campaign' => $orderData['attribution_campaign'] ?? ($attribution['campaign'] ?? null),
                'attribution_medium' => $orderData['attribution_medium'] ?? ($attribution['medium'] ?? null),
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

    public function recordStatus(Order $order, string $status, ?string $description = null, ?string $oldStatus = null, ?int $adminId = null): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status,
            'old_status' => $oldStatus,
            'description' => $description,
            'admin_id' => $adminId ?? auth('admin')->id(),
        ]);
    }

    /**
     * Create a manual order directly from the Admin Panel.
     *
     * @param  array<string, mixed>  $data
     * @param  Admin|null  $admin
     * @return Order
     */
    public function createManualOrder(array $data, ?Admin $admin = null): Order
    {
        return DB::transaction(function () use ($data, $admin) {
            // 1. Resolve or create customer User
            $user = null;
            if (! empty($data['user_id'])) {
                $user = User::find($data['user_id']);
            }

            $rawPhone = preg_replace('/\D/', '', (string) ($data['customer_phone'] ?? ''));
            $cleanPhone = strlen($rawPhone) >= 10 ? substr($rawPhone, -10) : $rawPhone;
            $email = ! empty($data['customer_email']) ? strtolower(trim((string) $data['customer_email'])) : null;
            $name = trim((string) ($data['customer_name'] ?? 'Customer'));

            if (! $user && $cleanPhone !== '') {
                $user = User::where(function ($q) use ($cleanPhone) {
                    $q->where('phone', $cleanPhone)
                        ->orWhere('phone', '+91'.$cleanPhone)
                        ->orWhere('phone', '0'.$cleanPhone);
                })->first();
            }

            if (! $user && $email !== null) {
                $user = User::where('email', $email)->first();
            }

            if (! $user) {
                $user = User::create([
                    'name' => $name,
                    'phone' => $cleanPhone ?: null,
                    'email' => $email,
                    'phone_verified_at' => now(),
                    'password' => bcrypt(Str::random(16)),
                    'status' => 'active',
                ]);
            }

            // 2. Format Addresses
            $shipping = [
                'full_name' => $data['shipping_name'] ?? $name,
                'mobile' => $data['shipping_mobile'] ?? $cleanPhone,
                'address_line1' => $data['shipping_address_line1'] ?? '',
                'address_line2' => $data['shipping_address_line2'] ?? null,
                'landmark' => $data['shipping_landmark'] ?? null,
                'city' => $data['shipping_city'] ?? '',
                'state' => $data['shipping_state'] ?? '',
                'pincode' => $data['shipping_pincode'] ?? '',
                'country' => $data['shipping_country'] ?? 'India',
            ];

            $isBillingSame = ! empty($data['is_billing_same']);
            if ($isBillingSame) {
                $billing = $shipping;
            } else {
                $billing = [
                    'full_name' => $data['billing_name'] ?? ($data['shipping_name'] ?? $name),
                    'mobile' => $data['billing_mobile'] ?? ($data['shipping_mobile'] ?? $cleanPhone),
                    'address_line1' => $data['billing_address_line1'] ?? ($data['shipping_address_line1'] ?? ''),
                    'address_line2' => $data['billing_address_line2'] ?? null,
                    'landmark' => $data['billing_landmark'] ?? null,
                    'city' => $data['billing_city'] ?? ($data['shipping_city'] ?? ''),
                    'state' => $data['billing_state'] ?? ($data['shipping_state'] ?? ''),
                    'pincode' => $data['billing_pincode'] ?? ($data['shipping_pincode'] ?? ''),
                    'country' => $data['billing_country'] ?? 'India',
                ];
            }

            // Save address to customer's address book if requested or if user has none
            if (! empty($data['save_customer_address']) || $user->addresses()->count() === 0) {
                $user->addresses()->firstOrCreate([
                    'address_line1' => $shipping['address_line1'],
                    'pincode' => $shipping['pincode'],
                ], [
                    'full_name' => $shipping['full_name'],
                    'mobile' => $shipping['mobile'],
                    'address_line2' => $shipping['address_line2'],
                    'landmark' => $shipping['landmark'],
                    'city' => $shipping['city'],
                    'state' => $shipping['state'],
                    'country' => $shipping['country'],
                    'type' => 'home',
                    'is_default' => $user->addresses()->count() === 0,
                ]);
            }

            // 3. Process Line Items
            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw new \InvalidArgumentException('Please add at least one product item to create an order.');
            }

            $rows = [];
            $subtotal = 0.0;
            $discountAmount = 0.0;

            foreach ($itemsData as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $variantId = ! empty($item['product_variant_id']) ? (int) $item['product_variant_id'] : null;
                $qty = max(1, (int) ($item['quantity'] ?? 1));

                $product = Product::with('images', 'categories')->findOrFail($productId);
                $variant = $variantId ? ProductVariant::with('images')->findOrFail($variantId) : null;

                $defaultMrp = (float) ($variant?->mrp ?? $product->mrp ?? 0);
                $mrp = isset($item['mrp']) && (float) $item['mrp'] > 0 ? (float) $item['mrp'] : $defaultMrp;

                $defaultPrice = (float) ($variant?->selling_price ?? $product->selling_price ?? 0);
                $price = isset($item['unit_price']) && is_numeric($item['unit_price']) && (float) $item['unit_price'] >= 0
                    ? (float) $item['unit_price']
                    : $defaultPrice;

                $gst = (float) ($variant?->gst_rate ?? $product->gst_rate ?? 0);

                $lineTotal = round($price * $qty, 2);
                $lineDiscount = max(0, ($mrp - $price) * $qty);

                $subtotal += $lineTotal;
                $discountAmount += $lineDiscount;

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

            // 4. Discounts & Coupons
            $coupon = null;
            $couponDiscount = 0.0;
            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::where('code', strtoupper(trim((string) $data['coupon_code'])))->first();
                if ($coupon && $coupon->is_active) {
                    if ($coupon->discount_type === 'percent') {
                        $couponDiscount = round(($subtotal * (float) $coupon->discount_value) / 100, 2);
                        if ($coupon->max_discount && $couponDiscount > (float) $coupon->max_discount) {
                            $couponDiscount = (float) $coupon->max_discount;
                        }
                    } else {
                        $couponDiscount = min($subtotal, (float) $coupon->discount_value);
                    }
                }
            }

            $manualDiscount = 0.0;
            if (! empty($data['discount_type']) && ! empty($data['discount_value'])) {
                $discVal = (float) $data['discount_value'];
                if ($data['discount_type'] === 'percent') {
                    $manualDiscount = round(($subtotal * $discVal) / 100, 2);
                } elseif ($data['discount_type'] === 'fixed') {
                    $manualDiscount = min($subtotal, $discVal);
                }
            }

            $totalOrderDiscount = round($couponDiscount + $manualDiscount, 2);
            $totalOrderDiscount = min($subtotal, $totalOrderDiscount);

            // Apportion discounts across items
            if ($subtotal > 0 && $totalOrderDiscount > 0) {
                $factor = max(0.0, ($subtotal - $totalOrderDiscount) / $subtotal);
                foreach ($rows as &$row) {
                    $row['net'] = round($row['total_price'] * $factor, 2);
                }
                unset($row);

                $reducedSubtotal = round(array_sum(array_column($rows, 'net')), 2);
                $drift = round((float) $subtotal - $totalOrderDiscount - $reducedSubtotal, 2);
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

            $shippingCharge = isset($data['shipping_charge']) ? max(0.0, (float) $data['shipping_charge']) : 0.0;
            $grandTotal = round(max(0, $subtotal + $shippingCharge - $totalOrderDiscount), 2);

            $intraState = $this->gstService->isIntraState($shipping['state'] ?? '');
            $taxSplit = $this->gstService->splitTax($taxAmount, $intraState);

            $paymentMethod = $data['payment_method'] ?? 'cod';
            $paymentStatus = $data['payment_status'] ?? ($paymentMethod === 'cod' ? 'pending' : 'paid');
            $orderStatus = $data['order_status'] ?? ($paymentStatus === 'paid' ? 'confirmed' : 'pending');

            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon?->id,
                'order_number' => 'TMP-'.Str::upper(Str::random(16)),
                ...$this->mapAddress('billing', $billing),
                ...$this->mapAddress('shipping', $shipping),
                'is_billing_same' => $isBillingSame,
                'subtotal' => $subtotal,
                'discount_amount' => round($discountAmount + $manualDiscount, 2),
                'coupon_discount' => $couponDiscount,
                'prepaid_discount' => 0.0,
                'shipping_charge' => $shippingCharge,
                'shipping_method' => $data['shipping_method'] ?? 'standard',
                'checkout_source' => 'admin',
                'taxable_amount' => $taxableAmount,
                'tax_amount' => $taxAmount,
                'cgst_amount' => $taxSplit['cgst'],
                'sgst_amount' => $taxSplit['sgst'],
                'igst_amount' => $taxSplit['igst'],
                'coupon_code' => $coupon ? strtoupper($coupon->code) : null,
                'coupon_type' => $coupon?->discount_type,
                'coupon_value' => $coupon ? (float) $coupon->discount_value : null,
                'grand_total' => $grandTotal,
                'amount_due' => $paymentStatus === 'paid' ? 0.0 : $grandTotal,
                'amount_paid' => $paymentStatus === 'paid' ? $grandTotal : 0.0,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'order_status' => $orderStatus,
                'internal_notes' => trim(
                    ($data['notes'] ?? '').
                    ($admin ? "\n[Created manually by Admin: {$admin->name}]" : '')
                ),
            ]);

            $order->update(['order_number' => generate_order_number($order->id)]);

            $reduceStock = ! isset($data['reduce_stock']) || (bool) $data['reduce_stock'];

            foreach ($rows as $row) {
                $row = Arr::except($row, 'net');
                OrderItem::create([...$row, 'order_id' => $order->id]);

                if ($reduceStock) {
                    $stockable = $row['product_variant_id']
                        ? ProductVariant::find($row['product_variant_id'])
                        : Product::find($row['product_id']);

                    if ($stockable) {
                        $this->inventoryService->withReference(
                            'sale',
                            $stockable,
                            $order,
                            -$row['quantity'],
                            "Manual Order {$order->order_number}"
                        );
                    }
                }
            }

            // Record payment if paid
            if ($paymentStatus === 'paid') {
                Payment::create([
                    'order_id' => $order->id,
                    'payment_reference' => ! empty($data['payment_reference'])
                        ? trim($data['payment_reference'])
                        : ('MANUAL-'.strtoupper(Str::random(10))),
                    'gateway' => in_array($paymentMethod, ['upi', 'bank_transfer', 'razorpay'], true) ? $paymentMethod : 'manual',
                    'method' => $paymentMethod,
                    'amount' => $grandTotal,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'gateway_response' => [
                        'created_by' => 'admin',
                        'admin_id' => $admin?->id,
                        'admin_name' => $admin?->name,
                        'reference' => $data['payment_reference'] ?? null,
                    ],
                ]);
            }

            // Record status history
            $this->recordStatus(
                $order,
                $orderStatus,
                'Manual order created by '.($admin ? "admin {$admin->name}" : 'admin'),
                null,
                $admin?->id
            );

            if ($coupon) {
                $this->couponService->recordUsage($coupon, $user, $order->id, $couponDiscount);
            }

            return $order->fresh(['items', 'payments', 'user', 'statusHistories']);
        });
    }

    public function cancelOrder(Order $order, ?string $reason = null): void
    {
        // The cancellability check must happen atomically with the cancellation
        // write: two concurrent cancel requests for the same order would both
        // pass an outside-transaction check, double-restore stock and create
        // duplicate refund records.
        DB::transaction(function () use ($order, $reason) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isCancellable()) {
                throw new \RuntimeException('This order can no longer be cancelled.');
            }

            $this->updateOrderStatus($locked, 'cancelled', $reason ?? 'Cancelled by customer');

            $locked->update(['cancellation_reason' => $reason]);

            foreach ($locked->items as $item) {
                $stockable = $item->product_variant_id
                    ? ProductVariant::find($item->product_variant_id)
                    : Product::find($item->product_id);

                if ($stockable) {
                    $this->inventoryService->withReference(
                        'reversal',
                        $stockable,
                        $locked,
                        $item->quantity,
                        "Stock restored for cancelled order {$locked->order_number}"
                    );
                }
            }

            // A paid order that is cancelled must produce a refund record for
            // the finance team rather than silently keeping the customer's
            // money. Pending/failed/abandoned payments are excluded.
            if ($locked->payment_status === 'paid' && (float) $locked->amount_paid > 0) {
                try {
                    app(RefundService::class)->createForOrder(
                        $locked,
                        $locked->user,
                        (float) $locked->amount_paid,
                        'full',
                        $reason ?? 'Order cancelled'
                    );
                } catch (\Throwable $e) {
                    Log::warning('Could not queue an automatic refund for a cancelled paid order.', [
                        'order' => $locked->order_number,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        // Admin alert is sent after the transaction commits so the finance team
        // is notified even when refund creation above failed. Failing to send
        // the alert itself must never break the cancellation.
        $order->refresh();

        // Auto-cancel in ShipMojo if already pushed
        try {
            $shipment = $order->shipments()->latest('id')->first();
            if ($shipment && $shipment->isPushedToShipMojo() && ! in_array($shipment->status, ['shipped', 'out_for_delivery', 'delivered'], true)) {
                app(\App\Services\ShipMojoService::class)->cancelOrder($order);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ShipMojo: Automatic cancel failed on order cancellation.', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }

        if ($order->payment_status === 'paid' && (float) $order->amount_paid > 0) {
            try {
                app(NotificationService::class)->notifyAdmins(
                    'Refund required',
                    sprintf(
                        'Order %s was cancelled after payment. A refund of %s needs processing.',
                        $order->order_number,
                        format_price((float) $order->amount_paid)
                    )
                );
            } catch (\Throwable $e) {
                Log::warning('Could not notify admins about a refund for a cancelled paid order.', [
                    'order' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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
