<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'region_id' => Region::factory(),
        ];
    }

    /**
     * Configure the model factory to create translations after model creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($city) {
            $city->translations()->create([
                'locale' => 'en',
                'title' => fake()->city(),
                'normalized_title' => fake()->slug(),
            ]);
        });
    }
}
