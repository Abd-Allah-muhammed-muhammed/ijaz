<?php

namespace Modules\Chat\Policies;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Model;

class ConversationPolicy
{
    /**
     * Can actor view/list messages in this conversation?
     */
    public function view(Model $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Can actor send messages in this conversation?
     */
    public function send(Model $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Can actor open/create a conversation for an operation?
     * Domain rules (OpportunityPolicy::chat, GuarantorPolicy::chat) run before open.
     */
    public function open(Model $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    /**
     * Check if actor is a participant (user1 or user2) in the conversation.
     */
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
