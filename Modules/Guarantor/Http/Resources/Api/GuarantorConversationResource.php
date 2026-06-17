<?php

namespace Modules\Guarantor\Http\Resources\Api;

use App\Models\Conversation;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Conversation */
class GuarantorConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester' => $this->buildParticipant($this->relationLoaded('user1') ? $this->user1 : null),
            'counterparty' => $this->buildParticipant($this->relationLoaded('user2') ? $this->user2 : null),
            'last_message' => $this->whenLoaded('lastMassage', fn () => [
                'content' => $this->lastMassage?->content,
                'created_at' => $this->lastMassage?->created_at?->toIso8601String(),
            ]),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'guarantor_request' => $this->whenLoaded('operation', fn () => [
                'id' => $this->operation->id,
                'title' => $this->operation->title,
                'status' => $this->operation->status->toArray(),
                'type' => $this->operation->type->toArray(),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildParticipant(mixed $user): ?array
    {
        if ($user instanceof JsonResource) {
            $user = $user->resource;
        }

        if (! $user) {
            return null;
        }

        return [
            'id' => $user->getKey(),
            'name' => $user->name ?? trim(($user->f_name ?? '').' '.($user->l_name ?? '')),
            'type' => $user instanceof User ? 'user' : ($user instanceof Provider ? 'provider' : 'unknown'),
            'image' => $user->image_url ?? null,
        ];
    }
}
