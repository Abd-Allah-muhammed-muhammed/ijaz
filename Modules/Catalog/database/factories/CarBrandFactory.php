<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\CarBrand;

/**
 * @extends Factory<CarBrand>
 */
class CarBrandFactory extends Factory
{
    protected $model = CarBrand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_active' => true,
            'image' => null,
        ];
    }

    /**
     * Configure the model factory to create translations after model creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (CarBrand $carBrand) {
            $carBrand->translations()->create([
                'locale' => 'en',
                'name' => fake()->randomElement(['Toyota', 'Honda', 'Ford', 'BMW', 'Mercedes-Benz', 'Hyundai']),
            ]);
        });
    }
}
