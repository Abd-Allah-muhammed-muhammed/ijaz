<?php

namespace App\Services\Chat\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProviderLocationChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public readonly Order $order, public readonly array $location) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Order.{$this->order->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'provider-location-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'location' => $this->location,
        ];
    }
}
