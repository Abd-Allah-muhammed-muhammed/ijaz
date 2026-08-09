<?php

namespace Modules\Reviews\Notifications;

use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Reviews\Models\Review;

/**
 * Peer review create/update — no status field, so DomainNotification (not StatusChangedNotification).
 */
class ReviewReceivedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public Review $review) {}

    protected function titleKey(): string
    {
        return 'review_received_title';
    }

    protected function bodyKey(): string
    {
        return 'review_received_body';
    }

    protected function payload(): array
    {
        return [
            'review_id' => $this->review->id,
            'rating' => $this->review->rating,
            'operation_type' => $this->review->operation_type,
            'operation_id' => $this->review->operation_id,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'rating' => (string) $this->review->rating,
            'screen' => 'review',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User;
    }

    public function broadcastType(): string
    {
        return 'review received';
    }
}
