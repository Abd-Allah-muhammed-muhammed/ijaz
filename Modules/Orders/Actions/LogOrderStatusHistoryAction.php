<?php

namespace Modules\Orders\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderStatusHistory;

class LogOrderStatusHistoryAction
{
    public function handle(
        Order $order,
        Model $actor,
        ?string $fromStatus,
        string $toStatus,
        ?string $reason = null,
        ?string $notes = null,
    ): OrderStatusHistory {
        return $order->histories()->create([
            'status' => $toStatus,
            'from_status' => $fromStatus,
            'actor_id' => $actor->getKey(),
            'actor_type' => $actor::class,
            'actor_name' => $actor->name ?? null,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }
}
