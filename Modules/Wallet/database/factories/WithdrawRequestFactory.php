<?php

namespace Modules\Wallet\Database\Factories;

use App\Enums\OperationStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Wallet\Models\WithdrawRequest;

/**
 * @extends Factory<WithdrawRequest>
 */
class WithdrawRequestFactory extends Factory
{
    protected $model = WithdrawRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 10, 500),
            'status' => OperationStatusEnum::Pending->value,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (WithdrawRequest $withdrawRequest): void {
            if ($withdrawRequest->user_id === null) {
                $user = User::factory()->create();
                $withdrawRequest->user_id = $user->getKey();
                $withdrawRequest->user_type = $user::class;
                $withdrawRequest->wallet_id = $user->wallet->id;
            }
        });
    }

    public function approved(): static
    {
        return $this->state(['status' => OperationStatusEnum::Approved]);
    }
}
