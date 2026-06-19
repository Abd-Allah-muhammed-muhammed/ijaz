<?php

namespace Database\Factories;

use App\Enums\SupportTickets\TicketSupportStatusEnum;
use App\Models\Order;
use App\Models\TicketSupport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketSupport>
 */
class TicketSupportFactory extends Factory
{
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
