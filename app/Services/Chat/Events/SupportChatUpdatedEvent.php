<?php

namespace App\Services\Chat\Events;

use App\Models\Chat;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportChatUpdatedEvent implements ShouldBroadcastNow, ShouldHandleEventsAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Chat $chat)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $type = str($this->chat->user2->getType())->plural();
        $id = $this->chat->user2->id;

        return [
            new PrivateChannel('support'),
            new PrivateChannel("{$type}.{$id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->chat->id,
            'user2' => [
                'id' => $this->chat->user2->id,
                'name' => $this->chat->user2->name,
                'image' => $this->chat->user2->image_path,
                'has_image' => $this->chat->user2->avatar || $this->chat->user2->image,
                'type' => $this->chat->user2->getType(),
            ],
            'unread_count' => $this->chat->unread_count ?? $this->chat->messages()->whereNull('read_at')
                ->where('sender_type', $this->chat->user2_type)
                ->where('sender_id', $this->chat->user2_id)
                ->count(),
            'last_message' => [
                'content' => $this->chat?->lastMassage?->content,
                'attachments_count' => $this->chat?->lastMassage?->attachments_count ?? $this->chat?->lastMassage?->attachments()?->count(),
                'sender' => [
                    'id' => $this->chat?->lastMassage?->sender?->id,
                    'name' => $this->chat?->lastMassage?->sender?->name,
                    'image' => $this->chat?->lastMassage?->sender?->image_path,
                    'has_image' => $this->chat?->lastMassage?->sender?->avatar || $this->chat->lastMassage?->sender?->image,
                    'type' => $this->chat?->lastMassage?->sender?->getType(),
                ],
            ],
            'last_message_at' => $this->chat->last_message_at ? Carbon::make($this->chat->last_message_at)->shortAbsoluteDiffForHumans() : '',
        ];
    }
}
