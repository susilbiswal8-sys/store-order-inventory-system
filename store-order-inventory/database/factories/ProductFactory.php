<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->unique()->bothify('SKU-###-??')),
            'price_per_unit' => fake()->randomFloat(2, 5, 500),
            'tax_percentage' => fake()->randomElement([0, 5, 8.25, 10, 12.5, 15]),
            'stock_on_hand' => fake()->numberBetween(10, 250),
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_on_hand' => fake()->numberBetween(0, 5),
        ]);
    }
}
