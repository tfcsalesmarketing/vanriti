<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\RazorpayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class RazorpayRefundTest extends TestCase
{
    use RefreshDatabase;

    protected function payment(array $overrides = []): Payment
    {
        $user = User::factory()->create();

        $order = Order::create([
            'order_number' => 'VAN-RF-'.Str::upper(Str::random(8)),
            'user_id' => $user->id,
            'billing_name' => 'Test User',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '42 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'billing_country' => 'India',
            'shipping_name' => 'Test User',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'shipping_country' => 'India',
            'subtotal' => 1000,
            'tax_amount' => 0,
            'grand_total' => 1000,
            'amount_paid' => 1000,
            'amount_due' => 0,
            'payment_method' => 'razorpay',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
        ]);

        return Payment::create(array_merge([
            'order_id' => $order->id,
            'payment_reference' => 'order_RAZ001',
        ], $overrides));
    }

    public function test_refund_posts_against_captured_payment_id_not_order_id(): void
    {
        $payment = $this->payment([
            'gateway_response' => ['webhook_payment_id' => 'pay_CAP001'],
        ]);

        Http::fake([
            'api.razorpay.com/v1/payments/pay_CAP001/refunds' => Http::response([
                'id' => 'rfnd_0001',
                'amount' => 10000,
                'status' => 'processed',
            ], 200),
        ]);

        $gateway = new RazorpayGateway;
        $result = $gateway->refund($payment, 100.00);

        $this->assertSame('rfnd_0001', $result['reference']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v1/payments/pay_CAP001/refunds')
                && ! str_contains($request->url(), 'order_RAZ001');
        });
    }

    public function test_refund_falls_back_to_legacy_payment_id_reference(): void
    {
        $payment = $this->payment([
            'gateway_response' => null,
            'payment_reference' => 'pay_LEGACY1',
        ]);

        Http::fake([
            'api.razorpay.com/v1/payments/pay_LEGACY1/refunds' => Http::response([
                'id' => 'rfnd_0002',
                'status' => 'processed',
            ], 200),
        ]);

        $gateway = new RazorpayGateway;
        $result = $gateway->refund($payment, 50.00);

        $this->assertSame('rfnd_0002', $result['reference']);
    }

    public function test_refund_throws_when_payment_id_is_missing(): void
    {
        $payment = $this->payment([
            'gateway_response' => null,
            'payment_reference' => 'order_RAZ001',
        ]);

        $this->expectException(\RuntimeException::class);

        (new RazorpayGateway)->refund($payment, 50.00);
    }

    public function test_refund_rethrows_gateway_failure(): void
    {
        $payment = $this->payment([
            'gateway_response' => ['webhook_payment_id' => 'pay_CAP001'],
        ]);

        Http::fake([
            'api.razorpay.com/v1/payments/pay_CAP001/refunds' => Http::response([
                'error' => ['description' => 'The payment is already fully refunded.'],
            ], 409),
        ]);

        $this->expectException(\RuntimeException::class);

        (new RazorpayGateway)->refund($payment, 10.00);
    }
}