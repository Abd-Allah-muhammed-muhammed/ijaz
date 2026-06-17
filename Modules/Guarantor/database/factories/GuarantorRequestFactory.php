<?php

namespace Modules\Guarantor\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Models\GuarantorRequest;

/**
 * @extends Factory<GuarantorRequest>
 */
class GuarantorRequestFactory extends Factory
{
    protected $model = GuarantorRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => GuarantorTypeEnum::Individual,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'requester_type' => User::class,
            'requester_id' => User::factory(),
            'counterparty_type' => User::class,
            'counterparty_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'fees' => 10,
            'status' => GuarantorStatusEnum::New,
        ];
    }

    public function company(): static
    {
        return $this->state(['type' => GuarantorTypeEnum::Company]);
    }

    public function approved(): static
    {
        return $this->state(['status' => GuarantorStatusEnum::Approved]);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => GuarantorStatusEnum::InProgress]);
    }
}
