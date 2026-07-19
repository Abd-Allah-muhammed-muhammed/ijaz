<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarType;

/**
 * @extends Factory<CarType>
 */
class CarTypeFactory extends Factory
{
    protected $model = CarType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'car_brand_id' => CarBrand::factory(),
            'is_active' => true,
            'image' => null,
        ];
    }

    /**
     * Configure the model factory to create translations after model creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (CarType $carType) {
            $carType->translations()->create([
                'locale' => 'en',
                'name' => fake()->randomElement(['Sedan', 'SUV', 'Hatchback', 'Coupe', 'Pickup', 'Van']),
            ]);
        });
    }
}
