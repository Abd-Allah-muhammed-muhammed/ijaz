<?php

namespace Modules\Opportunity\Policies;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Models\Opportunity;

class ConversationPolicy
{
    public function view(Model $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    public function send(Model $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    protected function isParticipant(Model $user, Conversation $conversation): bool
    {
        if ($conversation->operation_type !== Opportunity::class) {
            return false;
        }

        return ($conversation->user1_type === $user::class && $conversation->user1_id === $user->getKey())
            || ($conversation->user2_type === $user::class && $conversation->user2_id === $user->getKey());
    }
}
