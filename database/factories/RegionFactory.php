<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Region>
 */
class RegionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [];
    }

    /**
     * Configure the model factory to create translations after model creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($region) {
            $region->translations()->create([
                'locale' => 'en',
                'title' => fake()->city(),
                'normalized_title' => fake()->slug(),
            ]);
        });
    }
}
