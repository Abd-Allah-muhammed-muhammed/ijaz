<?php

namespace Modules\Payment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => null,
            'driver' => 'testing',
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'status' => PaymentStatusEnum::Pending,
            'message' => null,
            'url' => null,
            'request' => null,
            'response' => null,
        ];
    }

    public function forProduct(Model $product, Model $owner): static
    {
        return $this->state([
            'product_type' => $product::class,
            'product_id' => $product->getKey(),
            'user_id' => $owner->getKey(),
            'user_type' => $owner::class,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(['status' => PaymentStatusEnum::Accepted]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => PaymentStatusEnum::Rejected]);
    }
}
