<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\Analytics\EcommerceDataService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Step 7B: GA4 is implemented exclusively through GTM. This suite proves the
 * canonical dataLayer contract is fully GA4-compliant so the GTM container can
 * map the existing pushes to GA4 event tags (view_item, add_to_cart,
 * view_cart, begin_checkout, add_shipping_info, add_payment_info, purchase,
 * refund) without any code-level gtag.js or measurement ID.
 */
class GtmGa4TagContractTest extends TestCase
{
    use RefreshDatabase;

    protected const CANONICAL_EVENTS = [
        'view_item', 'add_to_cart', 'view_cart', 'begin_checkout',
        'add_shipping_info', 'add_payment_info', 'purchase', 'refund',
    ];

    protected const ECOMMERCE_CORE_KEYS = ['currency', 'value', 'items'];

    protected const EC_GA4_KEYS = [
        'currency', 'value', 'items', 'transaction_id', 'tax', 'shipping',
        'coupon', 'payment_type', 'shipping_tier',
    ];

    protected EcommerceDataService $e;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->e = app(EcommerceDataService::class);
        config(['analytics.gtm_container_id' => 'GTM-DEMO123']);
    }

    public function test_every_canonical_event_name_is_ga4_standard(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $cart = $this->seedCart($user, $product, 1);
        $order = $this->makeOrder($user, 'cod', 'pending', [], $product);
        $refund = $this->makeRefund($order, $user, 'full');

        $payloads = [
            $this->e->viewItem($product),
            $this->e->addToCart($product, null, 1),
            $this->e->viewCart($cart),
            $this->e->beginCheckout($cart),
            $this->e->addShippingInfo($cart, 'standard'),
            $this->e->addPaymentInfo($cart, 'cod'),
            $this->e->purchase($order),
            $this->e->refund($refund),
        ];

        $this->assertSame(self::CANONICAL_EVENTS, array_column($payloads, 'event'));

        foreach ($payloads as $payload) {
            $this->assertIsArray($payload['ecommerce']);
        }
    }

    public function test_item_objects_follow_ga4_item_schema(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $cart = $this->seedCart($user, $product, 2);
        $order = $this->makeOrder($user, 'cod', 'pending', [], $product);

        $sources = [
            $this->e->viewItem($product),
            $this->e->addToCart($product, null, 2),
            $this->e->viewCart($cart),
            $this->e->beginCheckout($cart),
            $this->e->addShippingInfo($cart, 'standard'),
            $this->e->addPaymentInfo($cart, 'cod'),
            $this->e->purchase($order),
        ];

        foreach ($sources as $payload) {
            $items = $payload['ecommerce']['items'];
            $this->assertGreaterThan(0, count($items));
            foreach ($items as $item) {
                $this->assertIsString($item['item_id']);
                $this->assertNotEmpty($item['item_id']);
                $this->assertIsString($item['item_name']);
                $this->assertNotEmpty($item['item_name']);
                $this->assertIsString($item['item_brand']);
                $this->assertNotEmpty($item['item_brand']);
                $this->assertIsNumeric($item['price']);
                $this->assertGreaterThanOrEqual(0, (float) $item['price']);
                $this->assertIsInt($item['quantity']);
                $this->assertGreaterThanOrEqual(1, $item['quantity']);
            }
        }
    }

    public function test_checkout_core_payloads_carry_ga4_currency_value_and_items(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $cart = $this->seedCart($user, $product, 2);

        $payloads = [
            $this->e->viewItem($product),
            $this->e->addToCart($product, null, 2),
            $this->e->viewCart($cart),
            $this->e->beginCheckout($cart),
        ];

        foreach ($payloads as $payload) {
            $ecommerce = $payload['ecommerce'];
            $this->assertSame('INR', $ecommerce['currency']);
            $this->assertIsNumeric($ecommerce['value']);
            $this->assertGreaterThan(0, (float) $ecommerce['value']);
            $this->assertSame(self::ECOMMERCE_CORE_KEYS, array_keys($ecommerce));
        }

        $shipping = $this->e->addShippingInfo($cart, 'standard');
        $this->assertSame(['currency', 'value', 'items', 'shipping_tier'], array_keys($shipping['ecommerce']));

        $payment = $this->e->addPaymentInfo($cart, 'cod');
        $this->assertSame(['currency', 'value', 'items', 'payment_type'], array_keys($payment['ecommerce']));
    }

    public function test_add_shipping_and_payment_info_carry_ga4_parameters(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $cart = $this->seedCart($user, $product, 1);

        $shipping = $this->e->addShippingInfo($cart, 'express');
        $this->assertSame('express', $shipping['ecommerce']['shipping_tier']);

        $paymentCod = $this->e->addPaymentInfo($cart, 'cod');
        $this->assertSame('cod', $paymentCod['ecommerce']['payment_type']);

        $paymentRazorpay = $this->e->addPaymentInfo($cart, 'razorpay');
        $this->assertSame('razorpay', $paymentRazorpay['ecommerce']['payment_type']);
    }

    public function test_purchase_payload_follows_ga4_purchase_schema(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'paid', ['coupon_code' => 'WELCOME10']);

        $purchase = $this->e->purchase($order);

        $this->assertSame('purchase', $purchase['event']);
        $this->assertSame($order->order_number, $purchase['ecommerce']['transaction_id']);
        $this->assertSame('INR', $purchase['ecommerce']['currency']);
        $this->assertEqualsWithDelta((float) $order->grand_total, (float) $purchase['ecommerce']['value'], 0.01);
        $this->assertIsNumeric($purchase['ecommerce']['tax']);
        $this->assertIsNumeric($purchase['ecommerce']['shipping']);
        $this->assertSame('WELCOME10', $purchase['ecommerce']['coupon']);
        $this->assertNotEmpty($purchase['ecommerce']['items']);
    }

    public function test_refund_payload_follows_ga4_refund_schema(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'paid');
        $refund = $this->makeRefund($order, $user, 'full');

        $refundPayload = $this->e->refund($refund);

        $this->assertSame('refund', $refundPayload['event']);
        $this->assertSame($order->order_number, $refundPayload['ecommerce']['transaction_id']);
        $this->assertSame('INR', $refundPayload['ecommerce']['currency']);
        $this->assertEqualsWithDelta((float) $refund->amount, (float) $refundPayload['ecommerce']['value'], 0.01);

        $this->assertNotEmpty($refundPayload['ecommerce']['items']);
        foreach ($refundPayload['ecommerce']['items'] as $item) {
            $this->assertLessThan(0, $item['quantity']);
            $this->assertIsString($item['item_id']);
            $this->assertNotEmpty($item['item_id']);
        }
    }

    public function test_no_legacy_ua_eec_objects_are_emitted(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $cart = $this->seedCart($user, $product, 1);
        $order = $this->makeOrder($user, 'cod', 'paid');
        $refund = $this->makeRefund($order, $user, 'full');

        $payloads = [
            $this->e->viewItem($product),
            $this->e->addToCart($product, null, 1),
            $this->e->viewCart($cart),
            $this->e->beginCheckout($cart),
            $this->e->addShippingInfo($cart, 'standard'),
            $this->e->addPaymentInfo($cart, 'cod'),
            $this->e->purchase($order),
            $this->e->refund($refund),
        ];

        foreach ($payloads as $payload) {
            foreach (array_keys($payload['ecommerce']) as $key) {
                $this->assertContains($key, self::EC_GA4_KEYS, "Unexpected ecommerce key [$key] for GA4.");
            }
        }
    }

    public function test_ga4_is_never_loaded_directly_when_gtm_is_enabled(): void
    {
        $product = $this->makeProduct();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringNotContainsString('googletagmanager.com/gtag/js', $html);
        $this->assertStringNotContainsString('gtag(', $html);
    }

    public function test_product_page_view_item_carries_ga4_fields_with_gtm_enabled(): void
    {
        $product = $this->makeProduct(['sku' => 'VNRT781', 'name' => 'VANRITI Repair Balm', 'selling_price' => 320.00, 'mrp' => 320.00]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('"event":"view_item"', $html);
        $this->assertStringContainsString('"currency":"INR"', $html);
        $this->assertStringContainsString('"item_id":"VNRT781"', $html);
        $this->assertStringContainsString('"item_name":"VANRITI Repair Balm"', $html);
        $this->assertStringContainsString('"item_brand":', $html);
        $this->assertStringContainsString('"price":320', $html);
        $this->assertStringContainsString('"quantity":1', $html);
        $this->assertSame(1, substr_count($html, '"event":"view_item"'));
    }

    public function test_cart_page_add_and_view_cart_carry_ga4_fields_with_gtm_enabled(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['sku' => 'VNRT782', 'name' => 'VANRITI Overnight Oil', 'selling_price' => 400.00, 'mrp' => 400.00]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 2])->assertRedirect(route('cart.index'));

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"add_to_cart"'));
        $this->assertSame(1, substr_count($html, '"event":"view_cart"'));
        $this->assertStringContainsString('"item_id":"VNRT782"', $html);
        $this->assertStringContainsString('"currency":"INR"', $html);
        $this->assertStringContainsString('"quantity":2', $html);
    }

    public function test_begin_checkout_carries_ga4_fields_with_gtm_enabled(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['sku' => 'VNRT783', 'name' => 'VANRITI Clay Mask', 'selling_price' => 260.00, 'mrp' => 260.00]);
        $this->seedCart($user, $product, 1);

        $html = $this->actingAs($user, 'web')->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"begin_checkout"'));
        $this->assertStringContainsString('"item_id":"VNRT783"', $html);
        $this->assertStringContainsString('"currency":"INR"', $html);
    }

    public function test_success_page_fires_ga4_funnel_and_purchase_once_with_gtm_enabled(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['sku' => 'VNRT785', 'name' => 'VANRITI Night Cream', 'selling_price' => 340.00, 'mrp' => 340.00]);
        $order = $this->makeOrder($user, 'cod', 'pending', [], $product);

        $html = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"add_shipping_info"'));
        $this->assertSame(1, substr_count($html, '"event":"add_payment_info"'));
        $this->assertSame(1, substr_count($html, '"event":"purchase"'));
        $this->assertStringContainsString('"payment_type":"cod"', $html);
        $this->assertStringContainsString('"transaction_id":"'.$order->order_number.'"', $html);
        $this->assertStringContainsString('"currency":"INR"', $html);
        $this->assertStringContainsString('"item_id":"VNRT785"', $html);
    }

    public function test_purchase_and_refund_transaction_ids_match_order_for_ga4_dedupe(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'paid');
        $refund = $this->makeRefund($order, $user, 'full');

        $purchase = $this->e->purchase($order);
        $refundPayload = $this->e->refund($refund);

        $this->assertSame($order->order_number, $purchase['ecommerce']['transaction_id']);
        $this->assertSame($order->order_number, $refundPayload['ecommerce']['transaction_id']);
    }

    protected function makeProduct(array $attributes = []): Product
    {
        return Product::factory()->active()->create(array_merge([
            'sku' => 'VNRT7B'.mt_rand(10000, 99999),
            'name' => 'VANRITI Soothing Balm',
            'selling_price' => 300.00,
            'mrp' => 300.00,
            'stock' => 10,
        ], $attributes));
    }

    protected function seedCart(User $user, Product $product, int $qty): Cart
    {
        $cart = Cart::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $qty,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => $product->gst_rate,
        ]);

        return $cart;
    }

    protected function makeOrder(User $user, string $paymentMethod, string $paymentStatus, array $extra = [], ?Product $product = null): Order
    {
        $product = $product ?? $this->makeProduct();

        $order = Order::create(array_merge([
            'order_number' => 'VAN-7B-'.str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'billing_name' => 'Ananya Rao',
            'billing_mobile' => '9812345670',
            'billing_address_line1' => '7 Residency Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560025',
            'shipping_name' => 'Ananya Rao',
            'shipping_mobile' => '9812345670',
            'shipping_address_line1' => '7 Residency Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560025',
            'subtotal' => 600,
            'grand_total' => 600,
            'amount_due' => 600,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status' => 'pending',
            'tax_amount' => 0,
            'shipping_charge' => 0,
        ], $extra));

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'mrp' => 300.00,
            'unit_price' => 300.00,
            'gst_rate' => 0,
            'tax_amount' => 0,
            'total_price' => 600.00,
        ]);

        return $order;
    }

    protected function makeRefund(Order $order, User $user, string $type): Refund
    {
        $returnRequest = ReturnRequest::create([
            'return_number' => 'RET-7B-'.str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Not as described',
            'status' => 'approved',
            'requested_at' => now(),
        ]);

        $orderItem = $order->items()->first();
        if ($type === 'full') {
            ReturnItem::create([
                'return_request_id' => $returnRequest->id,
                'order_item_id' => $orderItem->id,
                'quantity' => $orderItem->quantity,
                'refund_amount' => $orderItem->total_price,
            ]);
        }

        return Refund::create([
            'refund_number' => 'REF-7B-'.str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
            'return_request_id' => $returnRequest->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => $orderItem->total_price,
            'type' => $type,
            'status' => 'completed',
            'requested_at' => now(),
            'processed_at' => now(),
        ]);
    }
}