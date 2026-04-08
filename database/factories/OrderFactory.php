<?php

namespace Database\Factories;

use App\Enums\Order\OrderStatusEnum;
use App\Models\Category;
use App\Models\City;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'city_id' => City::factory(),
            'region_id' => Region::factory(),
            'price' => fake()->randomFloat(2, 100, 10000),
            'budget_start' => fake()->randomFloat(2, 100, 5000),
            'budget_end' => fake()->randomFloat(2, 5000, 10000),
            'status' => OrderStatusEnum::New,
            'expected_time' => fake()->numberBetween(1, 30),
        ];
    }

    /**
     * Indicate that the order is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatusEnum::InProgress,
        ]);
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatusEnum::EndedByClient,
        ]);
    }
}
