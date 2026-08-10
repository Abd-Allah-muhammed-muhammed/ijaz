<?php

namespace App\Notifications;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Shared base for Admin (dashboard) status-field changes that should notify the record owner.
 *
 * Use this ONLY when a genuine status column changes. Peer/entity events without a status
 * field (e.g. Reviews) must extend DomainNotification directly.
 *
 * Translation key convention: {domain}_status_{status}_{title|body}
 * Default Firebase: User and Provider notifiables (mobile + Provider dashboard web push).
 */
abstract class StatusChangedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    /**
     * Stable domain slug used in translation keys and broadcastType (e.g. withdraw, advisement).
     */
    abstract protected function domain(): string;

    /**
     * Normalized status string (approved, rejected, published, blocked, …).
     */
    abstract protected function statusValue(): string;

    /**
     * Entity identifiers stored on the database notification (after status).
     *
     * @return array<string, mixed>
     */
    abstract protected function entityPayload(): array;

    /**
     * Firebase `data` subset (deep-link fields).
     *
     * @return array<string, mixed>
     */
    abstract protected function entityFirebaseData(object $notifiable): array;

    protected function titleKey(): string
    {
        return $this->domain().'_status_'.$this->statusValue().'_title';
    }

    protected function bodyKey(): string
    {
        return $this->domain().'_status_'.$this->statusValue().'_body';
    }

    protected function payload(): array
    {
        return [
            'status' => $this->statusValue(),
            ...$this->entityPayload(),
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'status' => $this->statusValue(),
            ...$this->entityFirebaseData($notifiable),
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User || $notifiable instanceof Provider;
    }

    public function broadcastType(): string
    {
        return $this->domain().' status '.$this->statusValue();
    }
}
