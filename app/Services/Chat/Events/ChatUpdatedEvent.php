<?php

namespace App\Services\Chat\Events;

use App\Enums\Chat\ChatEventEnum;
use App\Models\Admin;
use App\Models\Conversation;
use App\Models\System;
use App\Services\Chat\Contracts\HasConversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatUpdatedEvent implements ShouldBroadcastNow, ShouldHandleEventsAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Model & HasConversation  $sender
     * @param  Model & HasConversation  $receiver
     * @return void
     */
    public function __construct(public Conversation $chat, public HasConversation $sender, public HasConversation $receiver)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->sender instanceof Admin) {
            $channels = [
                new PrivateChannel('systems.1'),
                new PrivateChannel($this->receiver->getAuthIdentifierForBroadcasting()),
            ];
        } elseif ($this->receiver instanceof System) {
            $channels = [
                new PrivateChannel('systems.1'),
                new PrivateChannel($this->sender->getAuthIdentifierForBroadcasting()),
            ];
        } else {
            $channels = [
                new PrivateChannel($this->receiver->getAuthIdentifierForBroadcasting()),
                new PrivateChannel($this->sender->getAuthIdentifierForBroadcasting()),
            ];
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return ChatEventEnum::Chat_Updated->value;
    }

    public function broadcastWith(): array
    {
        $user1 = $this->chat->user1()->is($this->sender) ? $this->sender : $this->receiver;
        $user2 = $this->chat->user2()->is($this->sender) ? $this->sender : $this->receiver;

        return [
            'id' => $this->chat->id,
            'user1' => [
                'id' => $user1->getKey(),
                'socket_id' => $user1->getAuthIdentifierForBroadcasting(),
                'name' => $user1->name,
                'image' => $user1->getImageUrl(),
                'online' => $user1->online,
            ],

            'user2' => [
                'id' => $user2->getKey(),
                'socket_id' => $user2->getAuthIdentifierForBroadcasting(),
                'name' => $user2->name,
                'image' => $user2->getImageUrl(),
                'online' => $user2->online,
            ],

            'unread_count' => $this->chat->unread_count ?: $this->chat->messages()->whereNull('read_at')
                ->whereMorphedTo('receiver', $this->sender)
                ->count(),
            'last_message' => [
                'content' => $this->chat?->lastMassage?->content,
                'attachments_count' => $this->chat?->lastMassage?->attachments_count ?: $this->chat?->lastMassage?->attachments()?->count(),
                'sender' => [
                    'id' => $this->chat?->lastMassage?->sender?->id,
                    'name' => $this->chat?->lastMassage?->sender?->name,
                    'image' => $this->chat?->lastMassage?->sender?->getImageUrl(),
                    'socket_id' => $this->chat?->lastMassage?->sender?->getAuthIdentifierForBroadcasting(),
                ],
                'read_at' => $this->chat?->lastMassage?->read_at,
            ],
            'last_massage_at' => $this->chat->last_message_at?->shortAbsoluteDiffForHumans(),
        ];
    }
}
