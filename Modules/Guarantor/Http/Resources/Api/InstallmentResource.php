<?php

namespace Modules\Guarantor\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Guarantor\Models\GuarantorInstallment;

/** @mixin GuarantorInstallment */
class InstallmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order' => $this->order,
            'amount' => $this->amount,
            'due_date' => $this->due_date->toDateString(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'released_at' => $this->released_at?->toIso8601String(),
            'status' => $this->status->toArray(),
        ];
    }
}
