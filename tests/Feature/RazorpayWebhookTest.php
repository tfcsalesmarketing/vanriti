<?php

namespace Tests\Feature;

use App\Models\AnalyticsConversion;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RazorpayWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Notification::fake();
    }

    protected function enableRazorpay(): void
    {
        Setting::updateOrCreate(['key' => 'online_payment_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_key_id'], ['value' => 'rzp_test_key']);
        Setting::updateOrCreate(['key' => 'razorpay_key_secret'], ['value' => \Illuminate\Support\Facades\Crypt::encryptString('rzp_test_secret')]);
        Setting::updateOrCreate(['key' => 'razorpay_webhook_secret'], ['value' => \Illuminate\Support\Facades\Crypt::encryptString('whsec_test')]);
    }

    protected function fakeRazorpayOrder(): void
    {
        Http::fake([
            'api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_EZ6G0001',
                'amount' => 160000,
                'currency' => 'INR',
                'receipt' => 'VAN-WH-0001',
            ], 200),
        ]);
    }

    protected function placeRazorpayOrder(User $user, Product $product, int $qty = 1): Order
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

        $this->actingAs($user, 'web')->post(route('checkout.store'), [
            'shipping_name' => 'Aarav Mehta',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'shipping_country' => 'India',
            'billing_same' => '1',
            'shipping_method' => 'standard',
            'payment_method' => 'razorpay',
            'notes' => null,
        ])->assertOk()->assertJson(['success' => true]);

        return Order::where('user_id', $user->id)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sign(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), 'whsec_test');
    }

    /**
     * @return array<string, mixed>
     */
    protected function capturedPayload(string $razorpayOrderId, int $amountPaisa, string $paymentId = 'pay_WH0001'): array
    {
        return [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $paymentId,
                        'order_id' => $razorpayOrderId,
                        'amount' => $amountPaisa,
                        'status' => 'captured',
                    ],
                ],
            ],
        ];
    }

    public function test_captured_webhook_settles_order_and_records_conversion(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 800.00,
            'mrp' => 800.00,
            'gst_rate' => 0,
            'stock' => 20,
        ]);

        $order = $this->placeRazorpayOrder($user, $product, 2);
        $payment = $order->payments()->firstOrFail();

        $payload = $this->capturedPayload('order_EZ6G0001', (int) round((float) $payment->amount * 100));

        $this->postJson(route('razorpay.webhook'), $payload, [
            'X-Razorpay-Signature' => $this->sign($payload),
        ])->assertOk()->assertJson(['status' => 'ok']);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertEqualsWithDelta((float) $order->amount_due, (float) $order->fresh()->amount_paid, 0.01);
        $this->assertSame('pay_WH0001', $payment->fresh()->gateway_response['webhook_payment_id']);

        $this->assertSame(1, AnalyticsConversion::where('event_type', 'purchase')
            ->where('order_number', $order->order_number)
            ->count());
    }

    public function test_replayed_captured_webhook_is_idempotent(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->placeRazorpayOrder($user, $product);
        $payment = $order->payments()->firstOrFail();
        $payload = $this->capturedPayload('order_EZ6G0001', (int) round((float) $payment->amount * 100));
        $headers = ['X-Razorpay-Signature' => $this->sign($payload)];

        $this->postJson(route('razorpay.webhook'), $payload, $headers)->assertOk();
        $this->postJson(route('razorpay.webhook'), $payload, $headers)
            ->assertOk()
            ->assertJson(['status' => 'already processed']);

        $this->assertSame(1, AnalyticsConversion::where('event_type', 'purchase')
            ->where('order_number', $order->order_number)
            ->count());
        $this->assertSame(1, $order->payments()->where('status', 'paid')->count());
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00, 'mrp' => 500.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->placeRazorpayOrder($user, $product);
        $payment = $order->payments()->firstOrFail();

        $payload = $this->capturedPayload('order_EZ6G0001', (int) round((float) $payment->amount * 100));

        $this->postJson(route('razorpay.webhook'), $payload, [
            'X-Razorpay-Signature' => 'not-a-valid-signature',
        ])->assertStatus(400);

        $this->assertNotSame('paid', $payment->fresh()->status);
        $this->assertSame('processing', $order->fresh()->payment_status);
    }

    public function test_captured_webhook_rejects_amount_mismatch(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00, 'mrp' => 500.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->placeRazorpayOrder($user, $product);
        $payment = $order->payments()->firstOrFail();

        $payload = $this->capturedPayload('order_EZ6G0001', (int) round((float) $payment->amount * 100) + 100);

        $this->postJson(route('razorpay.webhook'), $payload, [
            'X-Razorpay-Signature' => $this->sign($payload),
        ])->assertStatus(422);

        $this->assertNotSame('paid', $payment->fresh()->status);
        $this->assertSame('processing', $order->fresh()->payment_status);
    }

    public function test_webhook_returns_not_found_for_unknown_order(): void
    {
        $this->enableRazorpay();

        $payload = $this->capturedPayload('order_DOESNOTEXIST', 1000);

        $this->postJson(route('razorpay.webhook'), $payload, [
            'X-Razorpay-Signature' => $this->sign($payload),
        ])->assertStatus(404);
    }

    public function test_failed_webhook_cancels_order_and_restores_stock(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00, 'mrp' => 500.00, 'gst_rate' => 0, 'stock' => 20,
        ]);

        $order = $this->placeRazorpayOrder($user, $product, 2);
        $payment = $order->payments()->firstOrFail();

        $stockAfterOrder = (int) $product->fresh()->stock;
        $this->assertSame(18, $stockAfterOrder);

        $payload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_WHFAIL',
                        'order_id' => 'order_EZ6G0001',
                        'amount' => (int) round((float) $payment->amount * 100),
                        'status' => 'failed',
                    ],
                ],
            ],
        ];

        $this->postJson(route('razorpay.webhook'), $payload, [
            'X-Razorpay-Signature' => $this->sign($payload),
        ])->assertOk();

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(20, (int) $product->fresh()->stock);
    }
}
