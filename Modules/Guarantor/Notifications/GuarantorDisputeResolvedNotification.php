<?php

namespace Modules\Guarantor\Notifications;

use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;
use Modules\Guarantor\Models\GuarantorRequest;

/**
 * Admin resolved a dispute — DomainNotification with outcome-specific copy.
 */
class GuarantorDisputeResolvedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(
        public GuarantorRequest $guarantorRequest,
        public GuarantorDisputeResolutionEnum $resolution,
    ) {}

    protected function titleKey(): string
    {
        return match ($this->resolution) {
            GuarantorDisputeResolutionEnum::FullRequester => 'guarantor_dispute_resolved_full_requester_title',
            GuarantorDisputeResolutionEnum::FullCounterparty => 'guarantor_dispute_resolved_full_counterparty_title',
            GuarantorDisputeResolutionEnum::Escalate => 'guarantor_dispute_resolved_escalated_title',
        };
    }

    protected function bodyKey(): string
    {
        return match ($this->resolution) {
            GuarantorDisputeResolutionEnum::FullRequester => 'guarantor_dispute_resolved_full_requester_body',
            GuarantorDisputeResolutionEnum::FullCounterparty => 'guarantor_dispute_resolved_full_counterparty_body',
            GuarantorDisputeResolutionEnum::Escalate => 'guarantor_dispute_resolved_escalated_body',
        };
    }

    protected function payload(): array
    {
        return [
            'guarantor_request_id' => $this->guarantorRequest->id,
            'type' => $this->guarantorRequest->type->value,
            'resolution' => $this->resolution->value,
            'final_status' => $this->guarantorRequest->status->value,
        ];
    }

    protected function broadcastData(object $notifiable): array
    {
        return $this->firebaseData($notifiable);
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'guarantor_request_id' => $this->guarantorRequest->id,
            'resolution' => $this->resolution->value,
            'final_status' => $this->guarantorRequest->status->value,
            'screen' => 'guarantor',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User;
    }

    public function broadcastType(): string
    {
        return 'guarantor dispute resolved';
    }
}
