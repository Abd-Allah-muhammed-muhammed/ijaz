<?php

namespace App\Http\Resources\Dashboard;

use App\Http\Resources\Api\V1\GuaranteeRequestResource;
use App\Models\GuaranteeRequest;
use App\Models\Order;
use http\Exception\RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order | GuaranteeRequest */
class GeneralOperationUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => str(get_class($this->resource))->afterLast('\\')->title(),
            'show_url' => match (get_class($this->resource)) {
                Order::class => route('dashboard.orders.show', $this->id),
                GuaranteeRequest::class => route('dashboard.orders.show', $this->id),
                default => throw new RuntimeException('no op')
            },
            'data' => match (get_class($this->resource)) {
                Order::class => OrderResource::make($this),
                GuaranteeRequest::class => GuaranteeRequestResource::make($this),
                default => throw new RuntimeException('no op')
            },
        ];
    }
}
