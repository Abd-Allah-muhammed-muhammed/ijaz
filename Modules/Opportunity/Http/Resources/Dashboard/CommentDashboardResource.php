<?php

namespace Modules\Opportunity\Http\Resources\Dashboard;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Opportunity\Models\OpportunityComment;

/** @mixin OpportunityComment */
class CommentDashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->getKey(),
                'name' => $this->author instanceof User
                    ? $this->author->name
                    : ($this->author->name ?? ''),
                'type' => $this->author instanceof User ? 'user' : 'provider',
            ]),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
