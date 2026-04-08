<?php

namespace Database\Factories;

use App\Enums\GuaranteeRequest\GuaranteeRequestStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GuaranteeRequest>
 */
class GuaranteeRequestFactory extends Factory
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
            'amount' => fake()->randomFloat(2, 100, 5000),
            'fees' => fake()->randomFloat(2, 10, 500),
            'total' => fake()->randomFloat(2, 110, 5500),
            'status' => GuaranteeRequestStatusEnum::Pending,
            'user_type' => User::class,
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the guarantee request is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GuaranteeRequestStatusEnum::Approved,
        ]);
    }
}
