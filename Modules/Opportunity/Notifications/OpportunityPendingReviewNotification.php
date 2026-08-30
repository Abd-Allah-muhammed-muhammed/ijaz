<?php

namespace Modules\Opportunity\Notifications;

use App\Models\Admin;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Opportunity\Models\Opportunity;

/**
 * New opportunity awaiting Admin review (creation / resubmit, not a status-history event).
 */
class OpportunityPendingReviewNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public Opportunity $opportunity) {}

    protected function titleKey(): string
    {
        return 'opportunity_pending_review_title';
    }

    protected function bodyKey(): string
    {
        return 'opportunity_pending_review_body';
    }

    protected function payload(): array
    {
        return [
            'opportunity_id' => $this->opportunity->id,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'opportunity_id' => $this->opportunity->id,
            'screen' => 'opportunity',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof Admin;
    }

    public function broadcastType(): string
    {
        return 'opportunity pending review';
    }
}
