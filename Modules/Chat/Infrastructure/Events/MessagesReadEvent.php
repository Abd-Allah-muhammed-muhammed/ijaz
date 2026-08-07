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

/**
 * Notifies open conversation clients that specific messages were marked read
 * (2-party Option C — only when the designated receiver reads).
 */
class MessagesReadEvent implements ShouldBroadcastNow, ShouldHandleEventsAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  list<string>  $messageIds
     */
    public function __construct(
        public readonly string $conversationId,
        public readonly array $messageIds,
        public readonly string $readAt,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("chats.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return ChatEventEnum::Messages_Read->value;
    }

    /**
     * @return array{conversation_id: string, message_ids: list<string>, read_at: string}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_ids' => array_values($this->messageIds),
            'read_at' => $this->readAt,
        ];
    }
}
