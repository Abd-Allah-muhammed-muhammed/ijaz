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
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'fees' => $this->fees,
            'total' => $this->total,
            'status' => $this->status->toArray(),
            'project_type' => $this->project_type,
            'requester' => GuarantorParticipantResource::make($this->whenLoaded('requester')),
            'counterparty' => GuarantorParticipantResource::make($this->whenLoaded('counterparty')),
            'installments' => InstallmentResource::collection($this->whenLoaded('installments')),
            'company_detail' => CompanyDetailResource::make($this->whenLoaded('companyDetail')),
            'status_histories' => StatusHistoryResource::collection($this->whenLoaded('statusHistories')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'conversation_id' => $this->whenLoaded('conversation', fn () => $this->conversation?->id),
            'overdue_at' => $this->overdue_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
