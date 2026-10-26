<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PincodeService
{
    public function __construct(protected ShippingService $shippingService) {}

    /**
     * Check pincode serviceability, estimated delivery date, city/state, and COD availability.
     *
     * @return array<string, mixed>
     */
    public function check(string $pincode): array
    {
        $cleanPin = trim(preg_replace('/\D/', '', $pincode));

        if (strlen($cleanPin) !== 6) {
            return [
                'success' => false,
                'serviceable' => false,
                'message' => 'Please enter a valid 6-digit Indian postal pincode.',
            ];
        }

        $serviceable = $this->shippingService->isPincodeServiceable($cleanPin);

        if (! $serviceable) {
            return [
                'success' => false,
                'serviceable' => false,
                'pincode' => $cleanPin,
                'message' => 'We are currently unable to deliver to pincode ' . $cleanPin . '.',
            ];
        }

        $location = $this->resolveLocation($cleanPin);
        $timings = $this->calculateDeliveryTimelines($cleanPin);
        $codAvailable = (bool) setting('cod_enabled', true);
        $freeShippingThreshold = (float) (setting('free_shipping_threshold', 499) ?: 499);

        // Store pincode in session so other pages (cart, checkout) can access it
        if (session()) {
            session(['delivery_pincode' => $cleanPin]);
            if ($location['city']) {
                session(['delivery_city' => $location['city']]);
            }
            if ($location['state']) {
                session(['delivery_state' => $location['state']]);
            }
        }

        return [
            'success' => true,
            'serviceable' => true,
            'pincode' => $cleanPin,
            'city' => $location['city'],
            'state' => $location['state'],
            'location_display' => $location['display'],
            'estimated_delivery' => $timings['formatted_date'],
            'estimated_days_min' => $timings['days_min'],
            'estimated_days_max' => $timings['days_max'],
            'dispatch_note' => 'Dispatches within 24 hours',
            'cod_available' => $codAvailable,
            'free_shipping_threshold' => $freeShippingThreshold,
            'message' => 'Delivery available to ' . ($location['display'] ?: $cleanPin),
        ];
    }

    /**
     * Resolve District/City and State with 30-day cache and offline postal prefix fallback.
     *
     * @return array{city: string, state: string, display: string}
     */
    public function resolveLocation(string $pincode): array
    {
        $cacheKey = 'pincode_location_' . $pincode;

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($pincode) {
            try {
                $response = Http::timeout(2.5)
                    ->acceptJson()
                    ->get('https://api.postalpincode.in/pincode/' . $pincode);

                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json) && isset($json[0]['Status']) && $json[0]['Status'] === 'Success' && ! empty($json[0]['PostOffice'][0])) {
                        $po = $json[0]['PostOffice'][0];
                        $city = trim((string) ($po['District'] ?? ''));
                        $state = trim((string) ($po['State'] ?? ''));

                        if ($city !== '' || $state !== '') {
                            $display = $city && $state ? "{$city}, {$state}" : ($city ?: $state);

                            return [
                                'city' => $city,
                                'state' => $state,
                                'display' => $display,
                            ];
                        }
                    }
                }
            } catch (\Throwable) {
                // Network failure or timeout: proceed to offline prefix fallback
            }

            return $this->fallbackLocationByPrefix($pincode);
        });
    }

    /**
     * Compute delivery timeline based on Indian pincode zones.
     *
     * @return array{formatted_date: string, days_min: int, days_max: int}
     */
    protected function calculateDeliveryTimelines(string $pincode): array
    {
        $prefix = (int) substr($pincode, 0, 2);

        // Metro & primary hub prefixes (Delhi-NCR, Mumbai, Bengaluru, etc.)
        $isMetro = in_array($prefix, [11, 12, 20, 40, 56, 50, 60, 70], true);

        $daysMin = $isMetro ? 2 : 3;
        $daysMax = $isMetro ? 4 : 6;

        $targetDate = Carbon::now()->addDays($daysMin + 1);

        // Skip Sunday for standard delivery promise
        if ($targetDate->isSunday()) {
            $targetDate->addDay();
        }

        return [
            'formatted_date' => $targetDate->format('D, d M'),
            'days_min' => $daysMin,
            'days_max' => $daysMax,
        ];
    }

    /**
     * Offline fallback based on the official Indian Postal Pin Code mapping.
     *
     * @return array{city: string, state: string, display: string}
     */
    protected function fallbackLocationByPrefix(string $pincode): array
    {
        $p2 = substr($pincode, 0, 2);

        $states = [
            '11' => ['city' => 'New Delhi', 'state' => 'Delhi'],
            '12' => ['city' => 'Gurugram / Faridabad', 'state' => 'Haryana'],
            '13' => ['city' => 'Ambala / Panipat', 'state' => 'Haryana'],
            '14' => ['city' => 'Ludhiana / Jalandhar', 'state' => 'Punjab'],
            '15' => ['city' => 'Bathinda / Firozpur', 'state' => 'Punjab'],
            '16' => ['city' => 'Chandigarh', 'state' => 'Punjab / Chandigarh'],
            '17' => ['city' => 'Shimla / Kangra', 'state' => 'Himachal Pradesh'],
            '18' => ['city' => 'Jammu', 'state' => 'Jammu and Kashmir'],
            '19' => ['city' => 'Srinagar', 'state' => 'Jammu and Kashmir'],
            '20' => ['city' => 'Noida / Ghaziabad', 'state' => 'Uttar Pradesh'],
            '21' => ['city' => 'Kanpur / Allahabad', 'state' => 'Uttar Pradesh'],
            '22' => ['city' => 'Lucknow / Varanasi', 'state' => 'Uttar Pradesh'],
            '24' => ['city' => 'Dehradun / Haridwar', 'state' => 'Uttarakhand'],
            '26' => ['city' => 'Bareilly', 'state' => 'Uttar Pradesh'],
            '28' => ['city' => 'Agra / Mathura', 'state' => 'Uttar Pradesh'],
            '30' => ['city' => 'Jaipur', 'state' => 'Rajasthan'],
            '31' => ['city' => 'Udaipur', 'state' => 'Rajasthan'],
            '32' => ['city' => 'Kota', 'state' => 'Rajasthan'],
            '34' => ['city' => 'Jodhpur', 'state' => 'Rajasthan'],
            '36' => ['city' => 'Rajkot', 'state' => 'Gujarat'],
            '38' => ['city' => 'Ahmedabad', 'state' => 'Gujarat'],
            '39' => ['city' => 'Surat / Vadodara', 'state' => 'Gujarat'],
            '40' => ['city' => 'Mumbai / Thane', 'state' => 'Maharashtra'],
            '41' => ['city' => 'Pune', 'state' => 'Maharashtra'],
            '42' => ['city' => 'Nashik', 'state' => 'Maharashtra'],
            '43' => ['city' => 'Aurangabad', 'state' => 'Maharashtra'],
            '44' => ['city' => 'Nagpur', 'state' => 'Maharashtra'],
            '45' => ['city' => 'Indore', 'state' => 'Madhya Pradesh'],
            '46' => ['city' => 'Bhopal', 'state' => 'Madhya Pradesh'],
            '48' => ['city' => 'Jabalpur', 'state' => 'Madhya Pradesh'],
            '49' => ['city' => 'Raipur', 'state' => 'Chhattisgarh'],
            '50' => ['city' => 'Hyderabad', 'state' => 'Telangana'],
            '51' => ['city' => 'Tirupati / Kadapa', 'state' => 'Andhra Pradesh'],
            '52' => ['city' => 'Vijayawada / Guntur', 'state' => 'Andhra Pradesh'],
            '53' => ['city' => 'Visakhapatnam', 'state' => 'Andhra Pradesh'],
            '56' => ['city' => 'Bengaluru', 'state' => 'Karnataka'],
            '57' => ['city' => 'Mangaluru / Mysuru', 'state' => 'Karnataka'],
            '58' => ['city' => 'Hubballi / Belagavi', 'state' => 'Karnataka'],
            '60' => ['city' => 'Chennai', 'state' => 'Tamil Nadu'],
            '61' => ['city' => 'Thanjavur', 'state' => 'Tamil Nadu'],
            '62' => ['city' => 'Madurai', 'state' => 'Tamil Nadu'],
            '63' => ['city' => 'Salem / Vellore', 'state' => 'Tamil Nadu'],
            '64' => ['city' => 'Coimbatore', 'state' => 'Tamil Nadu'],
            '67' => ['city' => 'Kozhikode', 'state' => 'Kerala'],
            '68' => ['city' => 'Kochi / Ernakulam', 'state' => 'Kerala'],
            '69' => ['city' => 'Thiruvananthapuram', 'state' => 'Kerala'],
            '70' => ['city' => 'Kolkata', 'state' => 'West Bengal'],
            '71' => ['city' => 'Howrah', 'state' => 'West Bengal'],
            '74' => ['city' => 'North 24 Parganas', 'state' => 'West Bengal'],
            '75' => ['city' => 'Bhubaneswar / Cuttack', 'state' => 'Odisha'],
            '78' => ['city' => 'Guwahati', 'state' => 'Assam'],
            '79' => ['city' => 'Shillong / Agartala', 'state' => 'North East India'],
            '80' => ['city' => 'Patna', 'state' => 'Bihar'],
            '82' => ['city' => 'Gaya', 'state' => 'Bihar'],
            '83' => ['city' => 'Ranchi / Jamshedpur', 'state' => 'Jharkhand'],
        ];

        if (isset($states[$p2])) {
            $row = $states[$p2];

            return [
                'city' => $row['city'],
                'state' => $row['state'],
                'display' => "{$row['city']}, {$row['state']}",
            ];
        }

        return [
            'city' => '',
            'state' => '',
            'display' => 'India (' . $pincode . ')',
        ];
    }
}
