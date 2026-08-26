<?php

namespace Modules\Guarantor\Notifications;

use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Support\GuarantorFirebaseNotifiable;

/**
 * A party opened a dispute — DomainNotification (not StatusChangedNotification).
 */
class GuarantorDisputedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use GuarantorFirebaseNotifiable;

    public function __construct(
        public GuarantorRequest $guarantorRequest,
        public string $reason,
    ) {}

    protected function titleKey(): string
    {
        return 'guarantor_disputed_title';
    }

    protected function bodyKey(): string
    {
        return 'guarantor_disputed_body';
    }

    protected function payload(): array
    {
        return [
            'guarantor_request_id' => $this->guarantorRequest->id,
            'type' => $this->guarantorRequest->type->value,
            'reason' => $this->reason,
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
        return 'guarantor disputed';
    }
}
