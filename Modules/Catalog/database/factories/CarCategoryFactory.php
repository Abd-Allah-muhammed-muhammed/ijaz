<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\CarCategory;

/**
 * @extends Factory<CarCategory>
 */
class CarCategoryFactory extends Factory
{
    protected $model = CarCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'icon' => null,
            'parent_id' => null,
        ];
    }

    /**
     * Configure the model factory to create translations after model creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (CarCategory $carCategory) {
            $carCategory->translations()->create([
                'locale' => 'en',
                'title' => fake()->randomElement(['Passenger Cars', 'Commercial Vehicles', 'Luxury Cars', 'Sports Cars']),
            ]);
        });
    }
}
