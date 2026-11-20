<?php

namespace Database\Factories;

use App\Models\MarketplaceClick;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketplaceClick>
 */
class MarketplaceClickFactory extends Factory
{
    protected $model = MarketplaceClick::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => $this->faker->bothify('SKU-###??'),
            'marketplace' => $this->faker->randomElement(['amazon', 'flipkart']),
            'session_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'referrer' => $this->faker->url(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function amazon(): static
    {
        return $this->state(fn (array $attributes) => [
            'marketplace' => 'amazon',
        ]);
    }

    public function flipkart(): static
    {
        return $this->state(fn (array $attributes) => [
            'marketplace' => 'flipkart',
        ]);
    }
}

