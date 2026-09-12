<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGateway
{
    /**
     * Create an order in the payment gateway.
     *
     * @return array{id: string, gateway: string, redirect_url: ?string}
     */
    public function createOrder(Order $order, Payment $payment): array;

    /**
     * Verify a payment using gateway callback/webhook payload.
     */
    public function verify(Payment $payment, array $payload): bool;

    /**
     * Process a refund for a payment.
     */
    public function refund(Payment $payment, float $amount, string $reference = null): array;

    /**
     * @return array{key_id: string, currency: string}
     */
    public function publicConfig(): array;

    public function isEnabled(): bool;
}