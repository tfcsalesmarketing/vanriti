<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'parent_id' => null,
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.strtolower(Str::random(4)),
            'description' => $this->faker->paragraph(),
            'image' => null,
            'meta_title' => ucwords($name),
            'meta_description' => $this->faker->sentence(12),
            'meta_keywords' => implode(', ', $this->faker->words(5)),
            'status' => 'active',
            'sort_order' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}