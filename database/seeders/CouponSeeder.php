<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_cart_value' => 499,
                'max_discount' => 200,
                'starts_at' => null,
                'expires_at' => null,
                'usage_limit' => null,
                'per_customer_limit' => 1,
                'first_order_only' => true,
                'is_active' => true,
                'description' => '10% off your first order above Rs. 499. A little welcome gift.',
            ],
            [
                'code' => 'VANRITI15',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'min_cart_value' => 999,
                'max_discount' => 300,
                'starts_at' => null,
                'expires_at' => Carbon::now()->addMonths(3),
                'usage_limit' => 2000,
                'per_customer_limit' => 2,
                'first_order_only' => false,
                'is_active' => true,
                'description' => '15% off orders above Rs. 999. Up to Rs. 300 off.',
            ],
            [
                'code' => 'FLAT200',
                'discount_type' => 'fixed',
                'discount_value' => 200,
                'min_cart_value' => 1499,
                'max_discount' => null,
                'starts_at' => null,
                'expires_at' => null,
                'usage_limit' => null,
                'per_customer_limit' => 1,
                'first_order_only' => false,
                'is_active' => true,
                'description' => 'Flat Rs. 200 off on orders above Rs. 1499.',
            ],
            [
                'code' => 'FREESHIP',
                'discount_type' => 'fixed',
                'discount_value' => 49,
                'min_cart_value' => 299,
                'max_discount' => null,
                'starts_at' => null,
                'expires_at' => null,
                'usage_limit' => null,
                'per_customer_limit' => 5,
                'first_order_only' => false,
                'is_active' => true,
                'description' => 'Flat Rs. 49 off to cover standard shipping on orders above Rs. 299.',
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }
}