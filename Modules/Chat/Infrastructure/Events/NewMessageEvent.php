<?php

namespace Modules\Chat\Infrastructure\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Chat\Enums\ChatEventEnum;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Models\ConversationMessage;

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

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing(['sender', 'media']);

        // Must match ConversationMessageResource HTTP payload exactly (plain arrays).
        return json_decode(
            json_encode(ConversationMessageResource::make($this->message)->resolve()),
            true,
        );
    }
}
