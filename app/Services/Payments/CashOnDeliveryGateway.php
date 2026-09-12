<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

class CashOnDeliveryGateway implements PaymentGateway
{
    public function createOrder(Order $order, Payment $payment): array
    {
        return [
            'id' => 'COD-'.Str::upper(Str::random(12)),
            'gateway' => 'cod',
            'redirect_url' => null,
        ];
    }

    public function verify(Payment $payment, array $payload): bool
    {
        return true;
    }

    public function refund(Payment $payment, float $amount, string $reference = null): array
    {
        return [
            'status' => 'success',
            'reference' => $reference ?? 'COD-MANUAL-'.Str::upper(Str::random(8)),
        ];
    }

    public function publicConfig(): array
    {
        return ['key_id' => 'cod', 'currency' => 'INR'];
    }

    public function isEnabled(): bool
    {
        return (bool) setting('cod_enabled', true);
    }
}