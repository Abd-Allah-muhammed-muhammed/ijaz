<?php

namespace Modules\Support\Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Models\TicketSupport;

/**
 * @extends Factory<TicketSupport>
 */
class TicketSupportFactory extends Factory
{
    protected $model = TicketSupport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_type' => User::class,
            'user_id' => User::factory(),
            'operation_type' => Order::class,
            'operation_id' => Order::factory(),
            'title' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'status' => TicketSupportStatusEnum::Pending,
        ];
    }

    /**
     * Indicate that the ticket is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketSupportStatusEnum::Open,
        ]);
    }

    /**
     * Indicate that the ticket is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketSupportStatusEnum::Closed,
        ]);
    }
}
