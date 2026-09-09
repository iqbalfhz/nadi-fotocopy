<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Produk '.fake()->unique()->word();

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'price' => fake()->numberBetween(1, 100) * 500,
            'stock' => fake()->numberBetween(10, 200),
            'unit' => 'pcs',
            'type' => ProductType::Produk,
            'track_stock' => true,
            'is_active' => true,
        ];
    }

    /**
     * Jasa tidak melacak stok (§5.1).
     */
    public function jasa(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Jasa,
            'track_stock' => false,
            'stock' => 0,
            'unit' => 'lembar',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
