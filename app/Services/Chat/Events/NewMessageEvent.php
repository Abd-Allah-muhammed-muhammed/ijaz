<?php

namespace App\Services\Chat\Events;

use App\Enums\Chat\ChatEventEnum;
use App\Models\ConversationMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcastNow, ShouldHandleEventsAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public readonly ConversationMessage $message) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("chats.{$this->message->conversation_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return ChatEventEnum::New_Message->value;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'created_at' => $this->message->created_at->shortAbsoluteDiffForHumans(),
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'image' => $this->message->sender->getImageUrl(),
                'socket_id' => $this->message->sender->getAuthIdentifierForBroadcasting(),
            ],
            'attachments' => $this->message->attachments->map->toArray(),
            'read_at' => $this->message->read_at?->shortAbsoluteDiffForHumans(),
        ];
    }
}
