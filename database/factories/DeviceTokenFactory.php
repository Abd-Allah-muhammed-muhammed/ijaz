<?php

namespace Database\Factories;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceToken>
 */
class DeviceTokenFactory extends Factory
{
    protected $model = DeviceToken::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tokenable_type' => User::class,
            'tokenable_id' => User::factory(),
            'token' => fake()->unique()->sha256(),
            'platform' => fake()->randomElement(['ios', 'android', null]),
            'device_name' => fake()->optional()->userAgent(),
            'last_used_at' => now(),
        ];
    }
}
