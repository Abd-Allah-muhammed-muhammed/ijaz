<?php

namespace Modules\Opportunity\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;

/**
 * @extends Factory<OpportunityOffer>
 */
class OpportunityOfferFactory extends Factory
{
    protected $model = OpportunityOffer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'opportunity_id' => Opportunity::factory(),
            'author_type' => User::class,
            'author_id' => User::factory(),
            'price' => fake()->randomFloat(2, 100, 5000),
            'description' => fake()->sentence(),
            'status' => OfferStatusEnum::Pending,
        ];
    }
}
