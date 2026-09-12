<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function gatewayFor(string $method): PaymentGateway
    {
        return match ($method) {
            'cod' => new CashOnDeliveryGateway(),
            'razorpay' => new RazorpayGateway(),
            default => throw new \InvalidArgumentException("Unsupported payment method: {$method}"),
        };
    }

    public function availableMethods(): array
    {
        $methods = [];

        if ((bool) setting('cod_enabled', true)) {
            $methods['cod'] = [
                'label' => 'Cash on Delivery',
                'description' => 'Pay cash when your order is delivered.',
                'enabled' => true,
            ];
        }

        $razorpay = new RazorpayGateway();
        if ((bool) setting('online_payment_enabled', true)) {
            $methods['razorpay'] = [
                'label' => 'Online Payment',
                'description' => 'Pay securely via UPI, cards or net banking.',
                'enabled' => $razorpay->isEnabled(),
            ];
        }

        return $methods;
    }

    public function createPayment(Order $order, string $method): Payment
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => $method,
            'amount' => $order->amount_due,
            'status' => 'pending',
        ]);

        return $payment;
    }

    public function initialize(Order $order, Payment $payment, string $method): array
    {
        if ($method === 'cod') {
            $gateway = $this->gatewayFor('cod');
            $result = $gateway->createOrder($order, $payment);

            $payment->update([
                'payment_reference' => $result['id'],
                'gateway' => 'cod',
                'status' => 'pending',
            ]);

            $order->update([
                'payment_status' => 'pending',
            ]);

            return [...$result, 'amount' => $order->amount_due];
        }

        if ($method === 'razorpay') {
            $gateway = $this->gatewayFor('razorpay');

            if (! $gateway->isEnabled()) {
                throw new \RuntimeException('Online payments are currently unavailable.');
            }

            $result = $gateway->createOrder($order, $payment);

            $order->update([
                'payment_status' => 'processing',
            ]);

            return [...$result, 'amount' => $order->amount_due, 'config' => $gateway->publicConfig()];
        }

        throw new \InvalidArgumentException("Unsupported payment method: {$method}");
    }

    public function markPaid(Payment $payment): void
    {
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $order = $payment->order;

        $totalPaid = (float) $order->payments()->where('status', 'paid')->sum('amount');
        $outstanding = (float) $order->grand_total - $totalPaid;

        $order->update([
            'payment_status' => $outstanding <= 0 ? 'paid' : 'pending',
            'amount_paid' => $totalPaid,
            'amount_due' => max(0, $outstanding),
        ]);
    }

    public function markFailed(Payment $payment, string $reason = null): void
    {
        $payment->update([
            'status'    => 'failed',
            'failed_at' => now(),
        ]);

        $payment->order()->update([
            'payment_status' => 'failed',
        ]);
    }
}