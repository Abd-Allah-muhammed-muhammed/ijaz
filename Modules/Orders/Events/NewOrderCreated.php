<?php

namespace Modules\Orders\Events;

use App\Models\Provider;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Models\Order;

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
     * Fans out to each provider in the order's category on their own
     * private provider-{id} channel (ProviderLayout already subscribes there).
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return Provider::query()
            ->whereHas('categories', function ($query): void {
                $query->where('categories.id', $this->order->category_id);
            })
            ->pluck('id')
            ->map(fn (int|string $id): PrivateChannel => new PrivateChannel('provider-'.$id))
            ->all();
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
