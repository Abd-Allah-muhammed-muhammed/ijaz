<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\PropertyCategory;

/**
 * @extends Factory<PropertyCategory>
 */
class PropertyCategoryFactory extends Factory
{
    protected $model = PropertyCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'is_active' => true,
        ];
    }

    /**
     * Configure the model factory to create translations after model creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (PropertyCategory $category) {
            $category->translations()->create([
                'locale' => 'en',
                'title' => fake()->randomElement(['Residential', 'Commercial', 'Industrial', 'Agricultural']),
            ]);
        });
    }
}
