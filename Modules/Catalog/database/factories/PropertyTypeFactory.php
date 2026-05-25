<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\PropertyType;

/**
 * @extends Factory<PropertyType>
 */
class PropertyTypeFactory extends Factory
{
    protected $model = PropertyType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_active' => true,
        ];
    }

    /**
     * Configure the model factory to create translations after model creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (PropertyType $propertyType) {
            $propertyType->translations()->create([
                'locale' => 'en',
                'name' => fake()->randomElement(['Villa', 'Apartment', 'Land', 'Commercial', 'House', 'Studio']),
            ]);
        });
    }
}
