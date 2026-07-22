<?php

namespace Modules\Orders\Database\Factories;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;

/**
 * @extends Factory<OrderOffer>
 *
 * OrderOffer has no HasFactory trait (untouched in Orders Step 0).
 * Use OrderOfferFactory::new()->forOrder($order)->forProvider($provider)->create().
 */
class OrderOfferFactory extends Factory
{
    protected $model = OrderOffer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price' => fake()->randomFloat(2, 50, 5000),
            'description' => fake()->sentence(),
            'status' => OfferStatusEnum::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OfferStatusEnum::Pending,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OfferStatusEnum::Accepted,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OfferStatusEnum::Rejected,
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'category_id' => $order->category_id,
        ]);
    }

    public function forProvider(Provider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_id' => $provider->id,
        ]);
    }
}
