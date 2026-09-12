<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(4, true);
        $mrp = $this->faker->randomFloat(0, 199, 2499);
        $selling = round($mrp * 0.72, 2);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.strtolower(Str::random(4)),
            'sku' => 'VR-'.strtoupper(Str::random(8)),
            'barcode' => null,
            'short_description' => $this->faker->sentence(10),
            'description' => $this->faker->paragraphs(3, true),
            'highlights' => $this->faker->sentence(8),
            'ingredients' => implode(', ', $this->faker->words(8)),
            'benefits' => $this->faker->sentence(10),
            'how_to_use' => $this->faker->sentence(8),
            'directions' => null,
            'warnings' => null,
            'precautions' => null,
            'disclaimer' => null,
            'net_quantity' => $this->faker->randomElement(['50 ml', '100 ml', '150 g', '200 ml', '300 ml']),
            'unit' => $this->faker->randomElement(['ml', 'g']),
            'mrp' => $mrp,
            'selling_price' => $selling,
            'discount_percent' => round((($mrp - $selling) / $mrp) * 100, 1),
            'gst_rate' => $this->faker->randomElement([5, 12, 18]),
            'stock' => $this->faker->numberBetween(10, 200),
            'low_stock_threshold' => 5,
            'hsn_code' => $this->faker->randomElement(['3304', '3305', '3307', '3004']),
            'manufacturer' => 'VANRITI Naturals Pvt. Ltd.',
            'manufacturer_address' => $this->faker->address(),
            'country_of_origin' => 'India',
            'shelf_life' => '24 months',
            'expiry_info' => null,
            'video_url' => null,
            'meta_title' => ucwords($name),
            'meta_description' => $this->faker->sentence(12),
            'meta_keywords' => implode(', ', $this->faker->words(5)),
            'search_keywords' => implode(', ', $this->faker->words(6)),
            'status' => 'active',
            'is_featured' => $this->faker->boolean(25),
            'is_bestseller' => $this->faker->boolean(20),
            'is_new_arrival' => $this->faker->boolean(30),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function category(\App\Models\Category $category): static
    {
        return $this->afterCreating(function (Product $product) use ($category) {
            $product->categories()->syncWithoutDetaching([$category->id => ['is_primary' => true]]);
        });
    }
}