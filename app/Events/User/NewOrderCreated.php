<?php

namespace App\Events\User;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Order $order)
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
        return [
            new PrivateChannel('category.'.$this->order->category_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new-order';
    }

    public function broadcastWith(): array
    {
        $this->order->loadCount('offers', 'media');

        return [
            'id' => $this->order->id,
            'title' => $this->order->title,
            'description' => $this->order->description,
            'expected_time' => $this->order->expected_time,
            'budget_start' => $this->order->budget_start,
            'budget_end' => $this->order->budget_end,
            'category' => $this->order->category,
            'price' => $this->order->price,
            'status' => $this->order->status,
            'offers_count' => $this->order->offers_count,
            'created_at' => $this->order->created_at,
            'media_count' => $this->order->media_count,
        ];
    }
}
