<?php

namespace Database\Factories;

use App\Enums\CategoryFeesTypeEnum;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'icon' => fake()->imageUrl(),
            'fees' => fake()->randomFloat(2, 5, 50),
            'fees_type' => fake()->randomElement([
                CategoryFeesTypeEnum::FIXED,
                CategoryFeesTypeEnum::PERCENTAGE,
            ]),
        ];
    }

    /**
     * Configure the model factory to create translations after model creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($category) {
            $category->translations()->create([
                'locale' => 'en',
                'title' => fake()->sentence(2),
                'normalized_title' => fake()->slug(),
                'description' => fake()->paragraph(),
            ]);
        });
    }
}
