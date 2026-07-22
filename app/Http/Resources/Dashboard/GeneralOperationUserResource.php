<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Models\Order;

/** @mixin Order */
class GeneralOperationUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => str(get_class($this->resource))->afterLast('\\')->title(),
            'show_url' => route('dashboard.orders.show', $this->id),
            'data' => OrderResource::make($this),
        ];
    }
}
