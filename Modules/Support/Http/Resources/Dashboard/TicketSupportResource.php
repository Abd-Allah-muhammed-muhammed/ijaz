<?php

namespace Modules\Support\Http\Resources\Dashboard;

use App\Http\Resources\Dashboard\GeneralOperationUserResource;
use App\Http\Resources\Dashboard\OperationUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Support\Models\TicketSupport;

/**
 * @see  TicketSupport
 *
 * @mixin  TicketSupport
 */
class TicketSupportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            $this->mergeWhen($this->relationLoaded('operation'), function () {
                return [
                    'operation_type' => $this->operation_type,
                    'operation_id' => $this->operation_id,
                ];
            }),
            $this->mergeWhen($this->relationLoaded('user'), function () {
                return [
                    'user_type' => $this->user_type,
                    'user_id' => $this->user_id,
                ];
            }),
            'user' => OperationUserResource::make($this->whenLoaded('user')),
            'operation' => GeneralOperationUserResource::make($this->whenLoaded('operation')),
            'status' => $this->status->toArray(),
            'created_at' => $this->created_at,
        ];
    }
}
