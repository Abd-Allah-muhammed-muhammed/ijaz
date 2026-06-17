<?php

namespace Modules\Guarantor\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

/**
 * @extends Factory<GuarantorInstallment>
 */
class GuarantorInstallmentFactory extends Factory
{
    protected $model = GuarantorInstallment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guarantor_request_id' => GuarantorRequest::factory(),
            'order' => 1,
            'amount' => fake()->randomFloat(2, 100, 5000),
            'due_date' => now()->addDays(30),
            'status' => InstallmentStatusEnum::Pending,
        ];
    }

    public function overdue(): static
    {
        return $this->state([
            'due_date' => now()->subDays(10),
            'status' => InstallmentStatusEnum::Pending,
        ]);
    }

    public function paid(): static
    {
        return $this->state([
            'status' => InstallmentStatusEnum::Paid,
            'paid_at' => now(),
        ]);
    }
}
