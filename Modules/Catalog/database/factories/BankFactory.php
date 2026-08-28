<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Bank;

/**
 * @extends Factory<Bank>
 */
class BankFactory extends Factory
{
    protected $model = Bank::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Bank $bank) {
            if ($bank->translations()->where('locale', 'en')->exists()) {
                return;
            }

            $bank->translations()->create([
                'locale' => 'en',
                'name' => fake()->unique()->company().' Bank',
            ]);
        });
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
