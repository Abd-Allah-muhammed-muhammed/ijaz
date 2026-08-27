<?php

namespace Modules\Guarantor\Notifications;

use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Guarantor\Enums\GuarantorWithdrawnNotificationAudience;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Support\GuarantorFirebaseNotifiable;

/**
 * A party withdrew before payment — DomainNotification (not StatusChangedNotification).
 */
class GuarantorWithdrawnNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use GuarantorFirebaseNotifiable;

    public function __construct(
        public GuarantorRequest $guarantorRequest,
        public GuarantorWithdrawnNotificationAudience $audience,
        public ?string $reason,
    ) {}

    protected function titleKey(): string
    {
        return match ($this->audience) {
            GuarantorWithdrawnNotificationAudience::Withdrawer => 'guarantor_withdrawn_withdrawer_title',
            GuarantorWithdrawnNotificationAudience::OtherParty => 'guarantor_withdrawn_other_party_title',
            GuarantorWithdrawnNotificationAudience::Admin => 'guarantor_withdrawn_admin_title',
        };
    }

    protected function bodyKey(): string
    {
        return match ($this->audience) {
            GuarantorWithdrawnNotificationAudience::Withdrawer => 'guarantor_withdrawn_withdrawer_body',
            GuarantorWithdrawnNotificationAudience::OtherParty => 'guarantor_withdrawn_other_party_body',
            GuarantorWithdrawnNotificationAudience::Admin => 'guarantor_withdrawn_admin_body',
        };
    }

    protected function payload(): array
    {
        return [
            'guarantor_request_id' => $this->guarantorRequest->id,
            'type' => $this->guarantorRequest->type->value,
            'reason' => $this->reason,
            'final_status' => $this->guarantorRequest->status->value,
            'audience' => $this->audience->value,
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
            'final_status' => $this->guarantorRequest->status->value,
            'screen' => 'guarantor',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $this->guarantorPartyOrAdminReceivesFirebase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'guarantor withdrawn';
    }
}
