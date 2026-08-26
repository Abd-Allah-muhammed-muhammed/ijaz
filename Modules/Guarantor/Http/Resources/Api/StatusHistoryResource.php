<?php

namespace Modules\Guarantor\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Models\GuarantorStatusHistory;

/** @mixin GuarantorStatusHistory */
class StatusHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status
                ? GuarantorStatusEnum::from($this->from_status)->toArray()
                : null,
            'to_status' => GuarantorStatusEnum::from($this->to_status)->toArray(),
            'reason' => $this->reason,
            'notes' => $this->notes,
            'actor' => $this->whenLoaded('actor', fn () => GuarantorParticipantResource::make($this->actor)),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
