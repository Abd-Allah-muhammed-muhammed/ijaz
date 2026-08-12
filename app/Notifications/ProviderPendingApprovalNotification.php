<?php

namespace App\Notifications;

use App\Models\Admin;
use App\Models\Provider;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * New Provider registration awaiting Admin approval (creation event, not a status change).
 */
class ProviderPendingApprovalNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public Provider $provider) {}

    protected function titleKey(): string
    {
        return 'provider_pending_approval_title';
    }

    protected function bodyKey(): string
    {
        return 'provider_pending_approval_body';
    }

    protected function payload(): array
    {
        return [
            'provider_id' => $this->provider->id,
            'provider_name' => $this->provider->name,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'provider_id' => $this->provider->id,
            'screen' => 'provider',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof Admin;
    }

    public function broadcastType(): string
    {
        return 'provider pending approval';
    }
}
