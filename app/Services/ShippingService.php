<?php

namespace App\Services;

use App\Models\Address;

class ShippingService
{
    public function calculate(float $subtotal, ?Address $address = null, string $method = 'standard'): array
    {
        $freeThreshold = (float) (setting('free_shipping_threshold', 499) ?: 499);
        $charge = (float) (setting('shipping_charge', 49) ?: 49);

        if ($address && ! $this->isPincodeServiceable($address->pincode)) {
            throw new \RuntimeException('We are currently not delivering to this pincode.');
        }

        $thresholdExceeded = $subtotal >= $freeThreshold;
        $shippingCharge = $method === 'express'
            ? $charge + 50
            : $charge;

        if ($thresholdExceeded || $subtotal <= 0) {
            $shippingCharge = 0.0;
        }

        return [
            'method' => $method,
            'charge' => round($shippingCharge, 2),
            'free_threshold' => $freeThreshold,
            'eligible_for_free' => $thresholdExceeded,
            'estimated_days' => $method === 'express' ? '2-4' : setting('estimated_days', '3-7'),
            'serviceable' => true,
        ];
    }

    public function isPincodeServiceable(string $pincode): bool
    {
        if ((bool) setting('allow_pincode_check', true) === false) {
            return true;
        }

        $blocked = explode(',', (string) setting('blocked_pincodes', ''));

        return ! in_array($pincode, array_map('trim', $blocked), true);
    }

    public function validatePincode(string $pincode): bool
    {
        return preg_match('/^[0-9]{6}$/', $pincode) === 1;
    }
}