<?php

namespace Modules\Wallet\Notifications;

use App\Models\Admin;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Wallet\Models\WithdrawRequest;

/**
 * New withdraw request awaiting Admin review (creation event, not a status change).
 */
class WithdrawPendingReviewNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public WithdrawRequest $withdrawRequest) {}

    protected function titleKey(): string
    {
        return 'withdraw_pending_review_title';
    }

    protected function bodyKey(): string
    {
        return 'withdraw_pending_review_body';
    }

    protected function payload(): array
    {
        return [
            'withdraw_request_id' => $this->withdrawRequest->id,
            'amount' => $this->withdrawRequest->amount,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'withdraw_request_id' => $this->withdrawRequest->id,
            'screen' => 'withdrawRequest',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof Admin;
    }

    public function broadcastType(): string
    {
        return 'withdraw pending review';
    }
}
