<?php

namespace App\Services;

use App\Models\Address;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppShippingService
{
    public function __construct(
        protected ShippingService $shippingService,
        protected ShipMojoService $shipMojoService,
    ) {}

    /**
     * Calculate WhatsApp shipping charge.
     * Business rule:
     * - Subtotal < ₹499: ₹79 delivery charge.
     * - Subtotal >= ₹499: Free delivery.
     *
     * @return array{charge: float, free_threshold: float, eligible_for_free: bool, charge_paise: int}
     */
    public function calculate(float $subtotal): array
    {
        $freeThreshold = (float) (setting('whatsapp_free_shipping_threshold', 499) ?: 499);
        $standardCharge = (float) (setting('whatsapp_shipping_charge', 79) ?: 79);

        $eligibleForFree = $subtotal >= $freeThreshold || $subtotal <= 0;
        $charge = $eligibleForFree ? 0.0 : $standardCharge;

        return [
            'charge' => round($charge, 2),
            'free_threshold' => $freeThreshold,
            'eligible_for_free' => $eligibleForFree,
            'charge_paise' => (int) round($charge * 100),
        ];
    }

    /**
     * Validate 6-digit Indian PIN code format.
     */
    public function isValidPincodeFormat(string $pincode): bool
    {
        return (bool) preg_match('/^[0-9]{6}$/', trim($pincode));
    }

    /**
     * Check if delivery is serviceable to this PIN code.
     */
    public function isServiceable(string $pincode): bool
    {
        if (! $this->isValidPincodeFormat($pincode)) {
            return false;
        }

        // 1. Core blocked pincodes / allow_pincode_check setting
        if (! $this->shippingService->isPincodeServiceable($pincode)) {
            return false;
        }

        // 2. If ShipMojo courier service is enabled and configured, check serviceability
        try {
            if ((bool) setting('shipmojo_enabled', false) && ! empty(setting('shipmojo_public_key', ''))) {
                $pickupPincode = (string) setting('shipmojo_pickup_pincode', '110001');
                if ($pickupPincode !== '') {
                    $result = $this->shipMojoService->checkServiceability($pickupPincode, $pincode);
                    if (isset($result['status']) && $result['status'] === false) {
                        return false;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ShipMojo serviceability check exception in WhatsApp checkout: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Best-effort lookup of City and State for an Indian PIN code.
     *
     * @return array{city: ?string, state: ?string}
     */
    public function lookupPostalDetails(string $pincode): array
    {
        if (! $this->isValidPincodeFormat($pincode)) {
            return ['city' => null, 'state' => null];
        }

        try {
            $response = Http::timeout(4)->get("https://api.postalpincode.in/pincode/{$pincode}");
            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data[0]['PostOffice'][0])) {
                    $po = $data[0]['PostOffice'][0];
                    return [
                        'city' => $po['District'] ?? ($po['Block'] ?? null),
                        'state' => $po['State'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Silently fall back; customer enters or confirms city/state directly
        }

        return ['city' => null, 'state' => null];
    }
}
