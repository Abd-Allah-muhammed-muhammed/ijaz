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
            'status' => $this->status->toArray(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'released_at' => $this->released_at?->toIso8601String(),
            'overdue_notified_at' => $this->overdue_notified_at?->toIso8601String(),
            'is_past_due' => $this->isPastDue(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
