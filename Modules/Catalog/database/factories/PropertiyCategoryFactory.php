<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\PropertiyCategory;

/**
 * @extends Factory<PropertiyCategory>
 */
class PropertiyCategoryFactory extends Factory
{
    protected $model = PropertiyCategory::class;

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
        return $this->afterCreating(function (PropertiyCategory $category) {
            $category->translations()->create([
                'locale' => 'en',
                'title' => fake()->randomElement(['Residential', 'Commercial', 'Industrial', 'Agricultural']),
            ]);
        });
    }
}
