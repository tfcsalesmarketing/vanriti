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
     * Resolve District/City and State with 30-day cache, dual API lookups, and clean prefix fallback.
     *
     * @return array{city: string, state: string, display: string}
     */
    public function resolveLocation(string $pincode): array
    {
        $cleanPin = trim(preg_replace('/\D/', '', $pincode));
        if (strlen($cleanPin) !== 6) {
            return [
                'city' => '',
                'state' => '',
                'display' => '',
            ];
        }

        $cacheKey = 'pincode_location_' . $cleanPin;
        $cached = Cache::get($cacheKey);

        // Sanity-check cache: immediately purge legacy bad fallbacks (with slashes or empty values)
        if (is_array($cached) && ! empty($cached['city']) && ! empty($cached['state'])) {
            if (! str_contains((string) $cached['city'], '/') && ! str_contains((string) $cached['state'], '/')) {
                return $cached;
            }
            Cache::forget($cacheKey);
        }

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 VanritiStorefront/1.0';

        // 1. Primary: Official India Post API (District & State)
        try {
            $response = Http::withHeaders([
                'User-Agent' => $userAgent,
                'Accept' => 'application/json',
            ])->timeout(4.0)->get('https://api.postalpincode.in/pincode/' . $cleanPin);

            if ($response->successful()) {
                $json = $response->json();
                if (is_array($json) && isset($json[0]['Status']) && $json[0]['Status'] === 'Success' && ! empty($json[0]['PostOffice'][0])) {
                    $po = $json[0]['PostOffice'][0];
                    $district = trim((string) ($po['District'] ?? ''));
                    $division = trim((string) ($po['Division'] ?? ''));
                    $name = trim((string) ($po['Name'] ?? ''));
                    $state = trim((string) ($po['State'] ?? ''));

                    // For Gautam Buddha Nagar, refine city to Noida or Greater Noida if specified in Name/Block
                    $city = $district;
                    if ($district === 'Gautam Buddha Nagar') {
                        $block = (string) ($po['Block'] ?? '');
                        if (stripos($name, 'Greater Noida') !== false || stripos($block, 'Greater Noida') !== false) {
                            $city = 'Greater Noida';
                        } elseif (stripos($name, 'Noida') !== false || stripos($block, 'Noida') !== false) {
                            $city = 'Noida';
                        }
                    }

                    if ($city === '') {
                        $city = $division ?: $name;
                    }

                    if ($city !== '' && $state !== '') {
                        $result = [
                            'city' => $city,
                            'state' => $state,
                            'display' => "{$city}, {$state}",
                        ];
                        Cache::put($cacheKey, $result, now()->addDays(30));

                        return $result;
                    }
                }
            }
        } catch (\Throwable) {
            // Network failure or timeout: proceed to secondary provider
        }

        // 2. Secondary: Zippopotam Global Post API
        try {
            $response = Http::withHeaders([
                'User-Agent' => $userAgent,
                'Accept' => 'application/json',
            ])->timeout(3.0)->get('https://api.zippopotam.us/IN/' . $cleanPin);

            if ($response->successful()) {
                $json = $response->json();
                if (is_array($json) && ! empty($json['places'][0])) {
                    $place = $json['places'][0];
                    $city = trim((string) ($place['place name'] ?? ''));
                    $state = trim((string) ($place['state'] ?? ''));

                    if ($city !== '' && $state !== '') {
                        $result = [
                            'city' => $city,
                            'state' => $state,
                            'display' => "{$city}, {$state}",
                        ];
                        Cache::put($cacheKey, $result, now()->addDays(30));

                        return $result;
                    }
                }
            }
        } catch (\Throwable) {
            // Secondary failed: proceed to comprehensive offline mapping
        }

        // 3. Tertiary: Comprehensive offline Indian postal prefix mapping
        $fallback = $this->fallbackLocationByPrefix($cleanPin);
        if ($fallback['city'] !== '' && $fallback['state'] !== '') {
            // Cache fallback for only 1 day so live lookup retries once connectivity recovers
            Cache::put($cacheKey, $fallback, now()->addDay());
        }

        return $fallback;
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
     * Comprehensive offline Indian Postal Pin Code mapping by 3-digit and 2-digit prefixes.
     * Clean, single administrative city and official state name without compound slashes.
     *
     * @return array{city: string, state: string, display: string}
     */
    protected function fallbackLocationByPrefix(string $pincode): array
    {
        $p3 = substr($pincode, 0, 3);
        $p2 = substr($pincode, 0, 2);

        // Specific 3-digit sorting district overrides
        $districts3 = [
            // Delhi & NCR
            '110' => ['city' => 'New Delhi', 'state' => 'Delhi'],
            '121' => ['city' => 'Faridabad', 'state' => 'Haryana'],
            '122' => ['city' => 'Gurugram', 'state' => 'Haryana'],
            '123' => ['city' => 'Rewari', 'state' => 'Haryana'],
            '124' => ['city' => 'Rohtak', 'state' => 'Haryana'],
            '125' => ['city' => 'Hisar', 'state' => 'Haryana'],
            '126' => ['city' => 'Jind', 'state' => 'Haryana'],
            '127' => ['city' => 'Bhiwani', 'state' => 'Haryana'],
            '131' => ['city' => 'Sonipat', 'state' => 'Haryana'],
            '132' => ['city' => 'Panipat', 'state' => 'Haryana'],
            '133' => ['city' => 'Ambala', 'state' => 'Haryana'],
            '134' => ['city' => 'Panchkula', 'state' => 'Haryana'],
            '135' => ['city' => 'Yamunanagar', 'state' => 'Haryana'],
            '136' => ['city' => 'Kurukshetra', 'state' => 'Haryana'],

            // Punjab & Chandigarh
            '141' => ['city' => 'Ludhiana', 'state' => 'Punjab'],
            '142' => ['city' => 'Moga', 'state' => 'Punjab'],
            '143' => ['city' => 'Amritsar', 'state' => 'Punjab'],
            '144' => ['city' => 'Jalandhar', 'state' => 'Punjab'],
            '145' => ['city' => 'Pathankot', 'state' => 'Punjab'],
            '146' => ['city' => 'Hoshiarpur', 'state' => 'Punjab'],
            '147' => ['city' => 'Patiala', 'state' => 'Punjab'],
            '148' => ['city' => 'Sangrur', 'state' => 'Punjab'],
            '151' => ['city' => 'Bathinda', 'state' => 'Punjab'],
            '152' => ['city' => 'Firozpur', 'state' => 'Punjab'],
            '160' => ['city' => 'Chandigarh', 'state' => 'Chandigarh'],

            // Himachal Pradesh, J&K, Ladakh
            '171' => ['city' => 'Shimla', 'state' => 'Himachal Pradesh'],
            '172' => ['city' => 'Kinnaur', 'state' => 'Himachal Pradesh'],
            '173' => ['city' => 'Solan', 'state' => 'Himachal Pradesh'],
            '174' => ['city' => 'Bilaspur', 'state' => 'Himachal Pradesh'],
            '175' => ['city' => 'Mandi', 'state' => 'Himachal Pradesh'],
            '176' => ['city' => 'Kangra', 'state' => 'Himachal Pradesh'],
            '177' => ['city' => 'Hamirpur', 'state' => 'Himachal Pradesh'],
            '180' => ['city' => 'Jammu', 'state' => 'Jammu and Kashmir'],
            '182' => ['city' => 'Udhampur', 'state' => 'Jammu and Kashmir'],
            '190' => ['city' => 'Srinagar', 'state' => 'Jammu and Kashmir'],
            '191' => ['city' => 'Anantnag', 'state' => 'Jammu and Kashmir'],
            '192' => ['city' => 'Baramulla', 'state' => 'Jammu and Kashmir'],
            '194' => ['city' => 'Leh', 'state' => 'Ladakh'],

            // Uttar Pradesh & Uttarakhand
            '201' => substr($pincode, 0, 4) === '2010' ? ['city' => 'Ghaziabad', 'state' => 'Uttar Pradesh'] : ['city' => 'Noida', 'state' => 'Uttar Pradesh'],
            '202' => ['city' => 'Aligarh', 'state' => 'Uttar Pradesh'],
            '203' => ['city' => 'Bulandshahr', 'state' => 'Uttar Pradesh'],
            '204' => ['city' => 'Hathras', 'state' => 'Uttar Pradesh'],
            '205' => ['city' => 'Mainpuri', 'state' => 'Uttar Pradesh'],
            '206' => ['city' => 'Etawah', 'state' => 'Uttar Pradesh'],
            '207' => ['city' => 'Etah', 'state' => 'Uttar Pradesh'],
            '208' => ['city' => 'Kanpur', 'state' => 'Uttar Pradesh'],
            '209' => ['city' => 'Kanpur Dehat', 'state' => 'Uttar Pradesh'],
            '211' => ['city' => 'Prayagraj', 'state' => 'Uttar Pradesh'],
            '212' => ['city' => 'Kaushambi', 'state' => 'Uttar Pradesh'],
            '221' => ['city' => 'Varanasi', 'state' => 'Uttar Pradesh'],
            '222' => ['city' => 'Jaunpur', 'state' => 'Uttar Pradesh'],
            '224' => ['city' => 'Ayodhya', 'state' => 'Uttar Pradesh'],
            '225' => ['city' => 'Barabanki', 'state' => 'Uttar Pradesh'],
            '226' => ['city' => 'Lucknow', 'state' => 'Uttar Pradesh'],
            '227' => ['city' => 'Amethi', 'state' => 'Uttar Pradesh'],
            '228' => ['city' => 'Sultanpur', 'state' => 'Uttar Pradesh'],
            '229' => ['city' => 'Rae Bareli', 'state' => 'Uttar Pradesh'],
            '231' => ['city' => 'Mirzapur', 'state' => 'Uttar Pradesh'],
            '244' => ['city' => 'Moradabad', 'state' => 'Uttar Pradesh'],
            '245' => ['city' => 'Hapur', 'state' => 'Uttar Pradesh'],
            '246' => ['city' => 'Bijnor', 'state' => 'Uttar Pradesh'],
            '247' => ['city' => 'Saharanpur', 'state' => 'Uttar Pradesh'],
            '248' => ['city' => 'Dehradun', 'state' => 'Uttarakhand'],
            '249' => ['city' => 'Haridwar', 'state' => 'Uttarakhand'],
            '250' => ['city' => 'Meerut', 'state' => 'Uttar Pradesh'],
            '251' => ['city' => 'Muzaffarnagar', 'state' => 'Uttar Pradesh'],
            '261' => ['city' => 'Sitapur', 'state' => 'Uttar Pradesh'],
            '262' => ['city' => 'Pilibhit', 'state' => 'Uttar Pradesh'],
            '263' => ['city' => 'Nainital', 'state' => 'Uttarakhand'],
            '271' => ['city' => 'Gonda', 'state' => 'Uttar Pradesh'],
            '272' => ['city' => 'Basti', 'state' => 'Uttar Pradesh'],
            '273' => ['city' => 'Gorakhpur', 'state' => 'Uttar Pradesh'],
            '274' => ['city' => 'Deoria', 'state' => 'Uttar Pradesh'],
            '276' => ['city' => 'Azamgarh', 'state' => 'Uttar Pradesh'],
            '281' => ['city' => 'Mathura', 'state' => 'Uttar Pradesh'],
            '282' => ['city' => 'Agra', 'state' => 'Uttar Pradesh'],
            '283' => ['city' => 'Firozabad', 'state' => 'Uttar Pradesh'],
            '284' => ['city' => 'Jhansi', 'state' => 'Uttar Pradesh'],

            // Rajasthan
            '301' => ['city' => 'Alwar', 'state' => 'Rajasthan'],
            '302' => ['city' => 'Jaipur', 'state' => 'Rajasthan'],
            '303' => ['city' => 'Jaipur', 'state' => 'Rajasthan'],
            '305' => ['city' => 'Ajmer', 'state' => 'Rajasthan'],
            '311' => ['city' => 'Bhilwara', 'state' => 'Rajasthan'],
            '312' => ['city' => 'Chittorgarh', 'state' => 'Rajasthan'],
            '313' => ['city' => 'Udaipur', 'state' => 'Rajasthan'],
            '321' => ['city' => 'Bharatpur', 'state' => 'Rajasthan'],
            '324' => ['city' => 'Kota', 'state' => 'Rajasthan'],
            '332' => ['city' => 'Sikar', 'state' => 'Rajasthan'],
            '334' => ['city' => 'Bikaner', 'state' => 'Rajasthan'],
            '335' => ['city' => 'Sri Ganganagar', 'state' => 'Rajasthan'],
            '341' => ['city' => 'Nagaur', 'state' => 'Rajasthan'],
            '342' => ['city' => 'Jodhpur', 'state' => 'Rajasthan'],

            // Gujarat & UTs
            '360' => ['city' => 'Rajkot', 'state' => 'Gujarat'],
            '361' => ['city' => 'Jamnagar', 'state' => 'Gujarat'],
            '364' => ['city' => 'Bhavnagar', 'state' => 'Gujarat'],
            '370' => ['city' => 'Kutch', 'state' => 'Gujarat'],
            '380' => ['city' => 'Ahmedabad', 'state' => 'Gujarat'],
            '382' => ['city' => 'Gandhinagar', 'state' => 'Gujarat'],
            '390' => ['city' => 'Vadodara', 'state' => 'Gujarat'],
            '395' => ['city' => 'Surat', 'state' => 'Gujarat'],
            '396' => ['city' => 'Valsad', 'state' => 'Gujarat'],

            // Maharashtra & Goa
            '400' => ['city' => 'Mumbai', 'state' => 'Maharashtra'],
            '401' => ['city' => 'Thane', 'state' => 'Maharashtra'],
            '403' => ['city' => 'Panaji', 'state' => 'Goa'],
            '411' => ['city' => 'Pune', 'state' => 'Maharashtra'],
            '413' => ['city' => 'Solapur', 'state' => 'Maharashtra'],
            '414' => ['city' => 'Ahmednagar', 'state' => 'Maharashtra'],
            '416' => ['city' => 'Kolhapur', 'state' => 'Maharashtra'],
            '421' => ['city' => 'Kalyan', 'state' => 'Maharashtra'],
            '422' => ['city' => 'Nashik', 'state' => 'Maharashtra'],
            '431' => ['city' => 'Chhatrapati Sambhajinagar', 'state' => 'Maharashtra'],
            '440' => ['city' => 'Nagpur', 'state' => 'Maharashtra'],
            '444' => ['city' => 'Amravati', 'state' => 'Maharashtra'],

            // Madhya Pradesh & Chhattisgarh
            '452' => ['city' => 'Indore', 'state' => 'Madhya Pradesh'],
            '456' => ['city' => 'Ujjain', 'state' => 'Madhya Pradesh'],
            '462' => ['city' => 'Bhopal', 'state' => 'Madhya Pradesh'],
            '474' => ['city' => 'Gwalior', 'state' => 'Madhya Pradesh'],
            '482' => ['city' => 'Jabalpur', 'state' => 'Madhya Pradesh'],
            '490' => ['city' => 'Bhilai', 'state' => 'Chhattisgarh'],
            '492' => ['city' => 'Raipur', 'state' => 'Chhattisgarh'],
            '495' => ['city' => 'Bilaspur', 'state' => 'Chhattisgarh'],

            // Telangana & Andhra Pradesh
            '500' => ['city' => 'Hyderabad', 'state' => 'Telangana'],
            '506' => ['city' => 'Warangal', 'state' => 'Telangana'],
            '515' => ['city' => 'Anantapur', 'state' => 'Andhra Pradesh'],
            '517' => ['city' => 'Tirupati', 'state' => 'Andhra Pradesh'],
            '518' => ['city' => 'Kurnool', 'state' => 'Andhra Pradesh'],
            '520' => ['city' => 'Vijayawada', 'state' => 'Andhra Pradesh'],
            '522' => ['city' => 'Guntur', 'state' => 'Andhra Pradesh'],
            '530' => ['city' => 'Visakhapatnam', 'state' => 'Andhra Pradesh'],

            // Karnataka
            '560' => ['city' => 'Bengaluru', 'state' => 'Karnataka'],
            '570' => ['city' => 'Mysuru', 'state' => 'Karnataka'],
            '575' => ['city' => 'Mangaluru', 'state' => 'Karnataka'],
            '580' => ['city' => 'Hubballi', 'state' => 'Karnataka'],
            '585' => ['city' => 'Kalaburagi', 'state' => 'Karnataka'],
            '590' => ['city' => 'Belagavi', 'state' => 'Karnataka'],

            // Tamil Nadu, Puducherry, Kerala
            '600' => ['city' => 'Chennai', 'state' => 'Tamil Nadu'],
            '605' => ['city' => 'Puducherry', 'state' => 'Puducherry'],
            '620' => ['city' => 'Tiruchirappalli', 'state' => 'Tamil Nadu'],
            '625' => ['city' => 'Madurai', 'state' => 'Tamil Nadu'],
            '632' => ['city' => 'Vellore', 'state' => 'Tamil Nadu'],
            '636' => ['city' => 'Salem', 'state' => 'Tamil Nadu'],
            '641' => ['city' => 'Coimbatore', 'state' => 'Tamil Nadu'],
            '670' => ['city' => 'Kannur', 'state' => 'Kerala'],
            '673' => ['city' => 'Kozhikode', 'state' => 'Kerala'],
            '682' => ['city' => 'Kochi', 'state' => 'Kerala'],
            '686' => ['city' => 'Kottayam', 'state' => 'Kerala'],
            '691' => ['city' => 'Kollam', 'state' => 'Kerala'],
            '695' => ['city' => 'Thiruvananthapuram', 'state' => 'Kerala'],

            // West Bengal, Odisha, North East
            '700' => ['city' => 'Kolkata', 'state' => 'West Bengal'],
            '711' => ['city' => 'Howrah', 'state' => 'West Bengal'],
            '713' => ['city' => 'Durgapur', 'state' => 'West Bengal'],
            '721' => ['city' => 'Midnapore', 'state' => 'West Bengal'],
            '734' => ['city' => 'Siliguri', 'state' => 'West Bengal'],
            '737' => ['city' => 'Gangtok', 'state' => 'Sikkim'],
            '744' => ['city' => 'Port Blair', 'state' => 'Andaman and Nicobar Islands'],
            '751' => ['city' => 'Bhubaneswar', 'state' => 'Odisha'],
            '753' => ['city' => 'Cuttack', 'state' => 'Odisha'],
            '769' => ['city' => 'Rourkela', 'state' => 'Odisha'],
            '781' => ['city' => 'Guwahati', 'state' => 'Assam'],
            '786' => ['city' => 'Dibrugarh', 'state' => 'Assam'],
            '790' => ['city' => 'Itanagar', 'state' => 'Arunachal Pradesh'],
            '793' => ['city' => 'Shillong', 'state' => 'Meghalaya'],
            '795' => ['city' => 'Imphal', 'state' => 'Manipur'],
            '796' => ['city' => 'Aizawl', 'state' => 'Mizoram'],
            '797' => ['city' => 'Kohima', 'state' => 'Nagaland'],
            '799' => ['city' => 'Agartala', 'state' => 'Tripura'],

            // Bihar & Jharkhand
            '800' => ['city' => 'Patna', 'state' => 'Bihar'],
            '812' => ['city' => 'Bhagalpur', 'state' => 'Bihar'],
            '823' => ['city' => 'Gaya', 'state' => 'Bihar'],
            '826' => ['city' => 'Dhanbad', 'state' => 'Jharkhand'],
            '831' => ['city' => 'Jamshedpur', 'state' => 'Jharkhand'],
            '834' => ['city' => 'Ranchi', 'state' => 'Jharkhand'],
            '842' => ['city' => 'Muzaffarpur', 'state' => 'Bihar'],
            '854' => ['city' => 'Purnia', 'state' => 'Bihar'],
        ];

        if (isset($districts3[$p3])) {
            $row = $districts3[$p3];

            return [
                'city' => $row['city'],
                'state' => $row['state'],
                'display' => "{$row['city']}, {$row['state']}",
            ];
        }

        // Broad 2-digit circle fallbacks
        $circles2 = [
            '11' => ['city' => 'New Delhi', 'state' => 'Delhi'],
            '12' => ['city' => 'Gurugram', 'state' => 'Haryana'],
            '13' => ['city' => 'Ambala', 'state' => 'Haryana'],
            '14' => ['city' => 'Ludhiana', 'state' => 'Punjab'],
            '15' => ['city' => 'Bathinda', 'state' => 'Punjab'],
            '16' => ['city' => 'Chandigarh', 'state' => 'Chandigarh'],
            '17' => ['city' => 'Shimla', 'state' => 'Himachal Pradesh'],
            '18' => ['city' => 'Jammu', 'state' => 'Jammu and Kashmir'],
            '19' => ['city' => 'Srinagar', 'state' => 'Jammu and Kashmir'],
            '20' => ['city' => 'Noida', 'state' => 'Uttar Pradesh'],
            '21' => ['city' => 'Prayagraj', 'state' => 'Uttar Pradesh'],
            '22' => ['city' => 'Lucknow', 'state' => 'Uttar Pradesh'],
            '23' => ['city' => 'Mirzapur', 'state' => 'Uttar Pradesh'],
            '24' => ['city' => 'Dehradun', 'state' => 'Uttarakhand'],
            '25' => ['city' => 'Meerut', 'state' => 'Uttar Pradesh'],
            '26' => ['city' => 'Bareilly', 'state' => 'Uttar Pradesh'],
            '27' => ['city' => 'Gorakhpur', 'state' => 'Uttar Pradesh'],
            '28' => ['city' => 'Agra', 'state' => 'Uttar Pradesh'],
            '30' => ['city' => 'Jaipur', 'state' => 'Rajasthan'],
            '31' => ['city' => 'Udaipur', 'state' => 'Rajasthan'],
            '32' => ['city' => 'Kota', 'state' => 'Rajasthan'],
            '33' => ['city' => 'Bikaner', 'state' => 'Rajasthan'],
            '34' => ['city' => 'Jodhpur', 'state' => 'Rajasthan'],
            '36' => ['city' => 'Rajkot', 'state' => 'Gujarat'],
            '37' => ['city' => 'Bhuj', 'state' => 'Gujarat'],
            '38' => ['city' => 'Ahmedabad', 'state' => 'Gujarat'],
            '39' => ['city' => 'Surat', 'state' => 'Gujarat'],
            '40' => ['city' => 'Mumbai', 'state' => 'Maharashtra'],
            '41' => ['city' => 'Pune', 'state' => 'Maharashtra'],
            '42' => ['city' => 'Nashik', 'state' => 'Maharashtra'],
            '43' => ['city' => 'Chhatrapati Sambhajinagar', 'state' => 'Maharashtra'],
            '44' => ['city' => 'Nagpur', 'state' => 'Maharashtra'],
            '45' => ['city' => 'Indore', 'state' => 'Madhya Pradesh'],
            '46' => ['city' => 'Bhopal', 'state' => 'Madhya Pradesh'],
            '47' => ['city' => 'Gwalior', 'state' => 'Madhya Pradesh'],
            '48' => ['city' => 'Jabalpur', 'state' => 'Madhya Pradesh'],
            '49' => ['city' => 'Raipur', 'state' => 'Chhattisgarh'],
            '50' => ['city' => 'Hyderabad', 'state' => 'Telangana'],
            '51' => ['city' => 'Tirupati', 'state' => 'Andhra Pradesh'],
            '52' => ['city' => 'Vijayawada', 'state' => 'Andhra Pradesh'],
            '53' => ['city' => 'Visakhapatnam', 'state' => 'Andhra Pradesh'],
            '56' => ['city' => 'Bengaluru', 'state' => 'Karnataka'],
            '57' => ['city' => 'Mangaluru', 'state' => 'Karnataka'],
            '58' => ['city' => 'Hubballi', 'state' => 'Karnataka'],
            '59' => ['city' => 'Belagavi', 'state' => 'Karnataka'],
            '60' => ['city' => 'Chennai', 'state' => 'Tamil Nadu'],
            '61' => ['city' => 'Thanjavur', 'state' => 'Tamil Nadu'],
            '62' => ['city' => 'Madurai', 'state' => 'Tamil Nadu'],
            '63' => ['city' => 'Salem', 'state' => 'Tamil Nadu'],
            '64' => ['city' => 'Coimbatore', 'state' => 'Tamil Nadu'],
            '67' => ['city' => 'Kozhikode', 'state' => 'Kerala'],
            '68' => ['city' => 'Kochi', 'state' => 'Kerala'],
            '69' => ['city' => 'Thiruvananthapuram', 'state' => 'Kerala'],
            '70' => ['city' => 'Kolkata', 'state' => 'West Bengal'],
            '71' => ['city' => 'Howrah', 'state' => 'West Bengal'],
            '72' => ['city' => 'Midnapore', 'state' => 'West Bengal'],
            '73' => ['city' => 'Siliguri', 'state' => 'West Bengal'],
            '74' => ['city' => 'North 24 Parganas', 'state' => 'West Bengal'],
            '75' => ['city' => 'Bhubaneswar', 'state' => 'Odisha'],
            '76' => ['city' => 'Berhampur', 'state' => 'Odisha'],
            '77' => ['city' => 'Rourkela', 'state' => 'Odisha'],
            '78' => ['city' => 'Guwahati', 'state' => 'Assam'],
            '79' => ['city' => 'Shillong', 'state' => 'Meghalaya'],
            '80' => ['city' => 'Patna', 'state' => 'Bihar'],
            '81' => ['city' => 'Bhagalpur', 'state' => 'Bihar'],
            '82' => ['city' => 'Gaya', 'state' => 'Bihar'],
            '83' => ['city' => 'Ranchi', 'state' => 'Jharkhand'],
            '84' => ['city' => 'Muzaffarpur', 'state' => 'Bihar'],
            '85' => ['city' => 'Purnia', 'state' => 'Bihar'],
        ];

        if (isset($circles2[$p2])) {
            $row = $circles2[$p2];

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
