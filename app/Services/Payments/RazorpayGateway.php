<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RazorpayGateway implements PaymentGateway
{
    protected string $keyId;
    protected string $keySecret;

    public function __construct()
    {
        $this->keyId = (string) setting('razorpay_key_id', '');
        $this->keySecret = (string) secret_setting('razorpay_key_secret', '');
    }

    public function createOrder(Order $order, Payment $payment): array
    {
        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->asJson()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => (int) round($order->amount_due * 100),
                'currency' => 'INR',
                'receipt' => $order->order_number,
                'notes' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Unable to initialise payment with Razorpay: '.$response->body());
        }

        $data = $response->json();

        $payment->update([
            'payment_reference' => $data['id'],
            'gateway' => 'razorpay',
            'gateway_response' => $data,
        ]);

        return [
            'id' => $data['id'],
            'gateway' => 'razorpay',
            'redirect_url' => null,
        ];
    }

    public function verify(Payment $payment, array $payload): bool
    {
        $orderId = $payload['razorpay_order_id'] ?? null;
        $paymentId = $payload['razorpay_payment_id'] ?? null;
        $signature = $payload['razorpay_signature'] ?? null;

        if (! $orderId || ! $paymentId || ! $signature) {
            return false;
        }

        if ($payment->payment_reference !== $orderId) {
            return false;
        }

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->keySecret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Razorpay signature mismatch', ['payment' => $payment->id, 'payload' => $payload]);

            return false;
        }

        return true;
    }

    public function refund(Payment $payment, float $amount, string $reference = null): array
    {
        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->asJson()
            ->post('https://api.razorpay.com/v1/payments/'.$payment->payment_reference.'/refunds', [
                'amount' => (int) round($amount * 100),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Refund failed: '.$response->body());
        }

        return [
            'status' => 'success',
            'reference' => $response->json('id'),
        ];
    }

    public function publicConfig(): array
    {
        return [
            'key_id' => $this->keyId,
            'currency' => 'INR',
        ];
    }

    public function isEnabled(): bool
    {
        return (bool) setting('razorpay_enabled', false)
            && $this->keyId !== ''
            && $this->keySecret !== '';
    }
}