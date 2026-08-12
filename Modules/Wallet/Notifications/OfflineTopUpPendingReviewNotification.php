<?php

namespace Modules\Wallet\Notifications;

use App\Models\Admin;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Wallet\Models\TopUpRequest;

/**
 * Offline top-up request awaiting Admin review (creation event, not a status change).
 */
class OfflineTopUpPendingReviewNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public TopUpRequest $topUpRequest) {}

    protected function titleKey(): string
    {
        return 'offline_topup_pending_review_title';
    }

    protected function bodyKey(): string
    {
        return 'offline_topup_pending_review_body';
    }

    protected function payload(): array
    {
        return [
            'top_up_request_id' => $this->topUpRequest->id,
            'amount' => $this->topUpRequest->amount,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'top_up_request_id' => $this->topUpRequest->id,
            'screen' => 'topUpRequest',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof Admin;
    }

    public function broadcastType(): string
    {
        return 'offline top-up pending review';
    }
}
