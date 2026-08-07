<?php

namespace Modules\Chat\Infrastructure\Events;

use App\Models\Admin;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Chat\Contracts\HasConversation;
use Modules\Chat\Enums\ChatEventEnum;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\System;

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
            $this->chat->loadMissing(['user1', 'user2']);

            // Support tickets: System ↔ User — notify systems + the end user.
            if ($this->chat->user1_type === System::class || $this->chat->user2_type === System::class) {
                return [
                    new PrivateChannel('systems.1'),
                    new PrivateChannel($this->receiver->getAuthIdentifierForBroadcasting()),
                ];
            }

            // Admin intervention on bilateral chats (e.g. Order User ↔ Provider).
            $channels = [new PrivateChannel('systems.1')];

            foreach ([$this->chat->user1, $this->chat->user2] as $participant) {
                if ($participant instanceof HasConversation) {
                    $channels[] = new PrivateChannel($participant->getAuthIdentifierForBroadcasting());
                }
            }

            return $channels;
        }

        if ($this->receiver instanceof System) {
            return [
                new PrivateChannel('systems.1'),
                new PrivateChannel($this->sender->getAuthIdentifierForBroadcasting()),
            ];
        }

        return [
            new PrivateChannel($this->receiver->getAuthIdentifierForBroadcasting()),
            new PrivateChannel($this->sender->getAuthIdentifierForBroadcasting()),
        ];
    }

    public function broadcastAs(): string
    {
        return ChatEventEnum::Chat_Updated->value;
    }

    public function broadcastWith(): array
    {
        $this->chat->loadMissing(['lastMessage.sender', 'lastMessage.media']);

        // Admin is not a participant on order chats — keep real user1/user2 in the payload.
        if (
            $this->sender instanceof Admin
            && ! $this->chat->user1()->is($this->sender)
            && ! $this->chat->user2()->is($this->sender)
        ) {
            $user1 = $this->chat->user1;
            $user2 = $this->chat->user2;
        } else {
            $user1 = $this->chat->user1()->is($this->sender) ? $this->sender : $this->receiver;
            $user2 = $this->chat->user2()->is($this->sender) ? $this->sender : $this->receiver;
        }
        $lastMessage = $this->chat->lastMessage;
        $attachmentsCount = $lastMessage
            ? $lastMessage->getMedia('attachments')->count()
            : 0;

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
                'content' => $lastMessage?->content,
                'attachments_count' => $attachmentsCount,
                'has_attachments' => (bool) ($lastMessage?->has_attachments),
                'sender' => [
                    'id' => $lastMessage?->sender?->id,
                    'name' => $lastMessage?->sender?->name,
                    'image' => $lastMessage?->sender?->getImageUrl(),
                    'socket_id' => $lastMessage?->sender?->getAuthIdentifierForBroadcasting(),
                ],
                'read_at' => $lastMessage?->read_at,
            ],
            'last_message_at' => $this->chat->last_message_at?->shortAbsoluteDiffForHumans(),
            'last_massage_at' => $this->chat->last_message_at?->shortAbsoluteDiffForHumans(), // @deprecated typo — use last_message_at
            'last_message_at_iso' => $this->chat->last_message_at?->toIso8601String(),
        ];
    }
}
