<?php

namespace Modules\Guarantor\Notifications;

use App\Models\Admin;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Guarantor\Models\GuarantorRequest;

/**
 * New guarantor request awaiting Admin review (creation event, not a status change).
 */
class GuarantorPendingReviewNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public GuarantorRequest $guarantorRequest) {}

    protected function titleKey(): string
    {
        return 'guarantor_pending_review_title';
    }

    protected function bodyKey(): string
    {
        return 'guarantor_pending_review_body';
    }

    protected function payload(): array
    {
        return [
            'guarantor_request_id' => $this->guarantorRequest->id,
            'type' => $this->guarantorRequest->type->value,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'guarantor_request_id' => $this->guarantorRequest->id,
            'screen' => 'guarantor',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof Admin;
    }

    public function broadcastType(): string
    {
        return 'guarantor pending review';
    }
}
