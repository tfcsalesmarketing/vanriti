<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $mrp = $this->faker->randomFloat(0, 149, 999);
        $selling = round($mrp * 0.72, 2);

        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->randomElement(['Aloe Vera', 'Neem & Tulsi', 'Rose', 'Tea Tree', 'Lavender', 'Charcoal']),
            'sku' => 'VR-'.strtoupper(Str::random(8)),
            'mrp' => $mrp,
            'selling_price' => $selling,
            'discount_percent' => round((($mrp - $selling) / $mrp) * 100, 1),
            'stock' => $this->faker->numberBetween(10, 150),
            'low_stock_threshold' => 5,
            'weight' => $this->faker->randomElement(['50 g', '100 g', '150 ml', '200 ml']),
            'dimensions' => null,
            'image' => null,
            'barcode' => null,
            'hsn_code' => '3304',
            'gst_rate' => 18,
            'status' => 'active',
            'is_default' => false,
            'sort_order' => 0,
        ];
    }
}