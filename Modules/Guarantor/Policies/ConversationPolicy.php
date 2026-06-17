<?php

namespace Modules\Guarantor\Policies;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Model;

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

    private function isParticipant(Model $user, Conversation $conversation): bool
    {
        return (
            $conversation->user1_type === $user::class
            && (string) $conversation->user1_id === (string) $user->getKey()
        ) || (
            $conversation->user2_type === $user::class
            && (string) $conversation->user2_id === (string) $user->getKey()
        );
    }
}
