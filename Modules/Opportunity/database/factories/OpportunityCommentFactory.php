<?php

namespace Modules\Opportunity\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;

/**
 * @extends Factory<OpportunityComment>
 */
class OpportunityCommentFactory extends Factory
{
    protected $model = OpportunityComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'opportunity_id' => Opportunity::factory(),
            'author_type' => User::class,
            'author_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
