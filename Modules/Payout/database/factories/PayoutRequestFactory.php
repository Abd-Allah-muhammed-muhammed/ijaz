<?php

namespace Modules\Payout\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;
use Modules\Wallet\Models\WithdrawRequest;

/**
 * @extends Factory<PayoutRequest>
 */
class PayoutRequestFactory extends Factory
{
    protected $model = PayoutRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 10, 500),
            'status' => PayoutStatusEnum::Pending->value,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (PayoutRequest $payoutRequest): void {
            if ($payoutRequest->operation_id !== null && $payoutRequest->recipient_id !== null) {
                return;
            }

            $user = User::factory()->create();
            $withdrawRequest = WithdrawRequest::factory()->for($user, 'user')->create([
                'amount' => $payoutRequest->amount,
            ]);

            $payoutRequest->operation_type = $withdrawRequest::class;
            $payoutRequest->operation_id = $withdrawRequest->getKey();
            $payoutRequest->recipient_type = $user::class;
            $payoutRequest->recipient_id = $user->getKey();
        });
    }
}
