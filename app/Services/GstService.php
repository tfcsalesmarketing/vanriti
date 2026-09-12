<?php

namespace App\Services;

class GstService
{
    /**
     * Indian GST state codes (first two digits of GSTIN) + names + common abbreviations.
     */
    public const STATES = [
        '01' => ['Jammu and Kashmir', 'JK'],
        '02' => ['Himachal Pradesh', 'HP'],
        '03' => ['Punjab', 'PB'],
        '04' => ['Chandigarh', 'CH'],
        '05' => ['Uttarakhand', 'UK'],
        '06' => ['Haryana', 'HR'],
        '07' => ['Delhi', 'DL'],
        '08' => ['Rajasthan', 'RJ'],
        '09' => ['Uttar Pradesh', 'UP'],
        '10' => ['Bihar', 'BR'],
        '11' => ['Sikkim', 'SK'],
        '12' => ['Arunachal Pradesh', 'AR'],
        '13' => ['Nagaland', 'NL'],
        '14' => ['Manipur', 'MN'],
        '15' => ['Mizoram', 'MZ'],
        '16' => ['Tripura', 'TR'],
        '17' => ['Meghalaya', 'ML'],
        '18' => ['Assam', 'AS'],
        '19' => ['West Bengal', 'WB'],
        '20' => ['Jharkhand', 'JH'],
        '21' => ['Odisha', 'OD'],
        '22' => ['Chhattisgarh', 'CT'],
        '23' => ['Madhya Pradesh', 'MP'],
        '24' => ['Gujarat', 'GJ'],
        '26' => ['Dadra and Nagar Haveli and Daman and Diu', 'DN'],
        '27' => ['Maharashtra', 'MH'],
        '29' => ['Karnataka', 'KA'],
        '30' => ['Goa', 'GA'],
        '31' => ['Lakshadweep', 'LD'],
        '32' => ['Kerala', 'KL'],
        '33' => ['Tamil Nadu', 'TN'],
        '34' => ['Puducherry', 'PY'],
        '35' => ['Andaman and Nicobar Islands', 'AN'],
        '36' => ['Telangana', 'TS'],
        '37' => ['Andhra Pradesh', 'AP'],
        '38' => ['Ladakh', 'LA'],
    ];

    /**
     * Taxable (base) value recovered from a GST-inclusive amount.
     */
    public function taxableAmount(float $inclusive, float $gstRate): float
    {
        if ($gstRate <= 0) {
            return round($inclusive, 2);
        }

        return round($inclusive / (1 + ($gstRate / 100)), 2);
    }

    /**
     * GST embedded within a GST-inclusive amount.
     */
    public function taxAmount(float $inclusive, float $gstRate): float
    {
        if ($gstRate <= 0) {
            return 0.0;
        }

        return round($inclusive - $this->taxableAmount($inclusive, $gstRate), 2);
    }

    /**
     * State business is registered in (for CGST/SGST vs IGST determination).
     */
    public function businessState(): string
    {
        return trim((string) setting('business_state', 'Haryana'));
    }

    /**
     * True when the order ships within the same state (intra-state -> CGST + SGST).
     */
    public function isIntraState(string $shippingState): bool
    {
        $shippingCode = $this->codeFor($shippingState);
        $businessCode = $this->codeFor($this->businessState());

        if ($shippingCode !== null && $businessCode !== null) {
            return $shippingCode === $businessCode;
        }

        return $this->normalize($shippingState) === $this->normalize($this->businessState());
    }

    /**
     * @return array{cgst: float, sgst: float, igst: float}
     */
    public function splitTax(float $tax, bool $intraState): array
    {
        $tax = round($tax, 2);

        if ($intraState) {
            $cgst = round($tax / 2, 2);

            return ['cgst' => $cgst, 'sgst' => round($tax - $cgst, 2), 'igst' => 0.0];
        }

        return ['cgst' => 0.0, 'sgst' => 0.0, 'igst' => $tax];
    }

    /**
     * Resolve a state — by GST code (06), abbreviation (HR) or full name ("Haryana").
     */
    public function codeFor(string $state): ?string
    {
        $state = $this->normalize($state);

        if ($state === '') {
            return null;
        }

        if (preg_match('/^\d{2}$/', $state)) {
            return $state;
        }

        foreach (self::STATES as $code => [$name, $abbr]) {
            if ($this->normalize($name) === $state || $this->normalize($abbr) === $state) {
                return $code;
            }
        }

        return null;
    }

    protected function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['-', '_', '.', '(', ')', ',', "'", '  '], '', $value);

        return preg_replace('/\s+/', '', $value) ?? $value;
    }
}