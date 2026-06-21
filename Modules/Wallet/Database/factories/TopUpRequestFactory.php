<?php

namespace Modules\Wallet\Database\Factories;

use App\Enums\OperationStatusEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Wallet\Models\TopUpRequest;

/**
 * @extends Factory<TopUpRequest>
 */
class TopUpRequestFactory extends Factory
{
    protected $model = TopUpRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 10, 1000),
            'status' => OperationStatusEnum::Pending->value,
            'payment_method' => PaymentMethodEnum::Offline->value,
            'payment_status' => PaymentStatusEnum::Pending->value,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (TopUpRequest $topUpRequest): void {
            if ($topUpRequest->user_id === null) {
                $user = User::factory()->create();
                $topUpRequest->user_id = $user->getKey();
                $topUpRequest->user_type = $user::class;
                $topUpRequest->wallet_id = $user->wallet->id;
            }
        });
    }

    public function online(): static
    {
        return $this->state(['payment_method' => PaymentMethodEnum::Online->value]);
    }

    public function approved(): static
    {
        return $this->state(['status' => OperationStatusEnum::Approved->value]);
    }
}
