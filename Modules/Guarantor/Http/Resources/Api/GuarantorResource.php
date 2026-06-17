<?php

namespace Modules\Guarantor\Http\Resources\Api;

use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Guarantor\Models\GuarantorRequest;

/** @mixin GuarantorRequest */
class GuarantorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->toArray(),
            'status' => $this->status->toArray(),
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'fees' => $this->fees,
            'total' => $this->total,
            'project_type' => $this->project_type,
            'cancellation_reason' => $this->cancellation_reason,
            'requester' => $this->whenLoaded('requester', fn () => GuarantorParticipantResource::make($this->requester)),
            'counterparty' => $this->whenLoaded('counterparty', fn () => GuarantorParticipantResource::make($this->counterparty)),
            'installments' => $this->whenLoaded('installments', fn () => InstallmentResource::collection($this->installments)),
            'company_detail' => $this->whenLoaded('companyDetail', fn () => $this->companyDetail
                ? CompanyDetailResource::make($this->companyDetail)
                : null),
            'status_histories' => $this->whenLoaded('statusHistories', fn () => StatusHistoryResource::collection($this->statusHistories)),
            'installments_count' => $this->whenCounted('installments'),
            'media' => $this->whenLoaded('media', fn () => MediaResource::collection($this->media)),
            'overdue_at' => $this->overdue_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
