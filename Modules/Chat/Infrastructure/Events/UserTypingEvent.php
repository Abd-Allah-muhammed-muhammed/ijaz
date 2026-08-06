<?php

namespace Modules\Chat\Infrastructure\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Chat\Contracts\HasConversation;
use Modules\Chat\Enums\ChatEventEnum;
use Modules\Chat\Http\Resources\ChatUserResource;
use Modules\Chat\Models\Conversation;

class UserTypingEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Model&HasConversation  $actor
     */
    public function __construct(
        public readonly Conversation $conversation,
        public readonly Model $actor,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("chats.{$this->conversation->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return ChatEventEnum::Typing->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return json_decode(
            json_encode(ChatUserResource::make($this->actor)->resolve()),
            true,
        );
    }
}
