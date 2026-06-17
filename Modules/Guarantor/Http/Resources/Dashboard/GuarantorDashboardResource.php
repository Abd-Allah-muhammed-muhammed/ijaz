<?php

namespace Modules\Guarantor\Http\Resources\Dashboard;

use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Guarantor\Http\Resources\Api\CompanyDetailResource;
use Modules\Guarantor\Http\Resources\Api\GuarantorParticipantResource;
use Modules\Guarantor\Http\Resources\Api\InstallmentResource;
use Modules\Guarantor\Http\Resources\Api\StatusHistoryResource;
use Modules\Guarantor\Models\GuarantorRequest;

/** @mixin GuarantorRequest */
class GuarantorDashboardResource extends JsonResource
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
            'admin_notes' => $this->admin_notes,
            'requester' => GuarantorParticipantResource::make($this->requester),
            'counterparty' => GuarantorParticipantResource::make($this->counterparty),
            'installments' => InstallmentResource::collection($this->installments),
            'company_detail' => $this->companyDetail
                ? CompanyDetailResource::make($this->companyDetail)
                : null,
            'status_histories' => StatusHistoryResource::collection($this->statusHistories),
            'media' => MediaResource::collection($this->whenLoaded('media', fn () => $this->media)),
            'installments_count' => $this->whenCounted('installments'),
            'overdue_at' => $this->overdue_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
