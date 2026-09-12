<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\RefundTransaction;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\Analytics\EcommerceDataService;
use App\Services\OrderService;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentService;
use App\Services\RefundService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundAnalyticsHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function deliveredOrder(User $user, Product $product, int $qty = 2): Order
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

        $order = app(OrderService::class)->placeOrder($user, $cart->fresh(), [
            'billing' => [
                'full_name' => 'Aarav Mehta', 'mobile' => '9876543210',
                'address_line1' => '42 MG Road', 'city' => 'Bengaluru',
                'state' => 'Karnataka', 'pincode' => '560038', 'country' => 'India',
            ],
            'shipping' => [
                'full_name' => 'Aarav Mehta', 'mobile' => '9876543210',
                'address_line1' => '42 MG Road', 'city' => 'Bengaluru',
                'state' => 'Karnataka', 'pincode' => '560038', 'country' => 'India',
            ],
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'coupon_code' => null,
            'notes' => null,
        ]);

        $order->update(['order_status' => 'delivered']);

        Payment::create([
            'order_id' => $order->id,
            'method' => 'cod',
            'amount' => $order->amount_due,
            'status' => 'paid',
        ]);

        return $order->fresh();
    }

    protected function makeRefund(Order $order, User $user, int $returnQty = 1): Refund
    {
        $orderItem = $order->items()->firstOrFail();
        $amount = (float) round($orderItem->unit_price * $returnQty, 2);

        $returnRequest = ReturnRequest::create([
            'return_number' => 'RTR-2026-TEST-'.strtoupper(substr(md5((string) mt_rand()), 0, 6)),
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Damaged',
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        ReturnItem::create([
            'return_request_id' => $returnRequest->id,
            'order_item_id' => $orderItem->id,
            'quantity' => $returnQty,
            'refund_amount' => $amount,
        ]);

        return app(RefundService::class)->createFromReturn($returnRequest, $user, $amount, 'partial', 'Damaged');
    }

    protected function serviceWithGateway(PaymentGateway $gateway): RefundService
    {
        $paymentService = new class($gateway) extends PaymentService {
            public function __construct(private PaymentGateway $gateway)
            {
            }

            public function gatewayFor(string $method): PaymentGateway
            {
                return $this->gateway;
            }
        };

        return new RefundService($paymentService, app(EcommerceDataService::class));
    }

    public function test_successful_completion_stages_exactly_one_refund_payload(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => '6FRF-001',
            'name' => 'VANRITI Refund Serum',
            'selling_price' => 250.00,
            'mrp' => 250.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product, 2);
        $refund = $this->makeRefund($order, $user, 1);

        $completed = app(RefundService::class)->complete($refund);

        $this->assertSame('completed', $completed->status);

        $pending = cache()->get(RefundService::PENDING_REFUND_CACHE_KEY.$user->id);
        $this->assertIsArray($pending);
        $this->assertCount(1, $pending);
        $this->assertArrayHasKey($refund->id, $pending);

        $payload = $pending[$refund->id];

        $this->assertSame('refund', $payload['event']);
        $this->assertSame($order->order_number, $payload['ecommerce']['transaction_id']);
        $this->assertEqualsWithDelta(250.00, $payload['ecommerce']['value'], 0.01);
        $this->assertSame('INR', $payload['ecommerce']['currency']);

        $orderItem = $order->items()->firstOrFail();
        $this->assertCount(1, $payload['ecommerce']['items']);
        $item = $payload['ecommerce']['items'][0];
        $this->assertSame($orderItem->sku, $item['item_id']);
        $this->assertSame(-1, $item['quantity']);
        $this->assertEqualsWithDelta(250.00, $item['price'], 0.01);
    }

    public function test_recompleting_an_already_completed_refund_is_idempotent(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $refund = $this->makeRefund($order, $user);

        $gateway = new class implements PaymentGateway {
            public int $refundCalls = 0;

            public function refund(Payment $payment, float $amount, string $reference = null): array
            {
                $this->refundCalls++;

                return ['status' => 'success', 'reference' => 'REF-FAKE-'.$this->refundCalls];
            }

            public function createOrder(Order $order, Payment $payment): array
            {
                return ['id' => 'FAKE', 'gateway' => 'cod', 'redirect_url' => null];
            }

            public function verify(Payment $payment, array $payload): bool
            {
                return true;
            }

            public function publicConfig(): array
            {
                return ['key_id' => 'cod', 'currency' => 'INR'];
            }

            public function isEnabled(): bool
            {
                return true;
            }
        };

        $service = $this->serviceWithGateway($gateway);

        $service->complete($refund);
        $this->assertSame(1, $gateway->refundCalls);
        $this->assertSame(1, RefundTransaction::where('refund_id', $refund->id)->where('status', 'success')->count());

        $service->complete($refund->fresh());

        $this->assertSame(1, $gateway->refundCalls);
        $this->assertSame(1, RefundTransaction::where('refund_id', $refund->id)->where('status', 'success')->count());
        $this->assertSame('refunded', $order->fresh()->payment_status);

        $pending = cache()->get(RefundService::PENDING_REFUND_CACHE_KEY.$user->id);
        $this->assertIsArray($pending);
        $this->assertCount(1, $pending);
    }

    public function test_approving_a_refund_does_not_stage_refund_analytics(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 300.00,
            'mrp' => 300.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $refund = $this->makeRefund($order, $user);

        app(RefundService::class)->approve($refund);

        $this->assertSame('approved', $refund->status);
        $this->assertNull(cache()->get(RefundService::PENDING_REFUND_CACHE_KEY.$user->id));
    }

    public function test_processing_a_refund_does_not_stage_refund_analytics(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 300.00,
            'mrp' => 300.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $refund = $this->makeRefund($order, $user);

        app(RefundService::class)->process($refund);

        $this->assertSame('processing', $refund->status);
        $this->assertNull(cache()->get(RefundService::PENDING_REFUND_CACHE_KEY.$user->id));
    }

    public function test_gateway_failure_does_not_stage_analytics_and_does_not_complete_refund(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 400.00,
            'mrp' => 400.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $refund = $this->makeRefund($order, $user);

        $gateway = new class implements PaymentGateway {
            public function refund(Payment $payment, float $amount, string $reference = null): array
            {
                throw new \RuntimeException('Gateway rejected refund');
            }

            public function createOrder(Order $order, Payment $payment): array
            {
                return ['id' => 'FAKE', 'gateway' => 'cod', 'redirect_url' => null];
            }

            public function verify(Payment $payment, array $payload): bool
            {
                return true;
            }

            public function publicConfig(): array
            {
                return ['key_id' => 'cod', 'currency' => 'INR'];
            }

            public function isEnabled(): bool
            {
                return true;
            }
        };

        $service = $this->serviceWithGateway($gateway);

        try {
            $service->complete($refund);
            $this->fail('Expected a RuntimeException from gateway failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Gateway rejected refund', $e->getMessage());
        }

        $this->assertNotSame('completed', $refund->fresh()->status);
        $this->assertSame(0, RefundTransaction::where('refund_id', $refund->id)->where('status', 'success')->count());
        $this->assertNull(cache()->get(RefundService::PENDING_REFUND_CACHE_KEY.$user->id));
    }

    public function test_storefront_render_consumes_pending_refund_exactly_once_and_refresh_does_not_replay(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 350.00,
            'mrp' => 350.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $refund = $this->makeRefund($order, $user);

        app(RefundService::class)->complete($refund);

        $html = $this->actingAs($user, 'web')->get(route('home'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"refund"'));
        $this->assertStringContainsString('"transaction_id":"'.$order->order_number.'"', $html);
        $this->assertNull(cache()->get(RefundService::PENDING_REFUND_CACHE_KEY.$user->id));

        $refresh = $this->actingAs($user, 'web')->get(route('home'))->assertOk()->getContent();
        $this->assertSame(0, substr_count($refresh, '"event":"refund"'));
    }

    public function test_refund_handoff_payload_is_escaped_on_storefront_render(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => '6FRF-ESC',
            'name' => "Curly \"Oil\" & Co <Script> O'Neem",
            'selling_price' => 150.00,
            'mrp' => 150.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $refund = $this->makeRefund($order, $user);

        app(RefundService::class)->complete($refund);

        $html = $this->actingAs($user, 'web')->get(route('home'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"refund"'));
        $this->assertStringContainsString('\u003C', $html);
        $this->assertStringContainsString('\u003E', $html);
        $this->assertStringContainsString('\u0026', $html);
        $this->assertStringContainsString('\u0027', $html);
        $this->assertStringContainsString('\u0022', $html);
        $this->assertStringNotContainsString('<Script>', $html);
    }

    public function test_refund_handoff_and_add_to_cart_handoff_are_independent(): void
    {
        $user = User::factory()->create();
        $refundProduct = Product::factory()->active()->create(['sku' => '6FRF-J1', 'selling_price' => 120.00, 'stock' => 10]);
        $order = $this->deliveredOrder($user, $refundProduct);
        $refund = $this->makeRefund($order, $user);
        app(RefundService::class)->complete($refund);

        $cartProduct = Product::factory()->active()->create(['sku' => '6FRF-J2', 'selling_price' => 80.00, 'stock' => 10]);

        $this->actingAs($user, 'web')->post(route('cart.add', $cartProduct), ['quantity' => 1]);

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"refund"'));
        $this->assertSame(1, substr_count($html, '"event":"add_to_cart"'));
        $this->assertSame(1, substr_count($html, '"event":"view_cart"'));

        $refresh = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();
        $this->assertSame(0, substr_count($refresh, '"event":"refund"'));
        $this->assertSame(0, substr_count($refresh, '"event":"add_to_cart"'));
        $this->assertSame(1, substr_count($refresh, '"event":"view_cart"'));
    }
}