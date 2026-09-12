<?php

namespace Database\Factories;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        $discountType = $this->faker->randomElement(['percentage', 'fixed']);

        return [
            'code' => strtoupper($this->faker->bothify('WELCOME??')),
            'discount_type' => $discountType,
            'discount_value' => $discountType === 'percentage' ? $this->faker->randomElement([5, 10, 15, 20, 25]) : $this->faker->randomElement([100, 200, 300]),
            'min_cart_value' => $this->faker->randomElement([0, 499, 999]),
            'max_discount' => $discountType === 'percentage' ? $this->faker->randomElement([null, 300, 500]) : null,
            'starts_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->addDays(30),
            'usage_limit' => null,
            'per_customer_limit' => 1,
            'first_order_only' => $this->faker->boolean(30),
            'is_active' => true,
            'description' => $this->faker->sentence(8),
        ];
    }
}