<?php

namespace App\Services\Chat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotificationSendEvent implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(protected User $user, protected ?int $count = null) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {

        return [
            new PrivateChannel($this->user->receivesBroadcastNotificationsOn()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new-unread-notification';
    }

    public function broadcastWith(): array
    {
        return [
            'count' => $this->count ?? $this->user->unreadNotifications()->count(),
        ];
    }
}
