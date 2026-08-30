<?php

namespace Modules\Opportunity\Notifications;

use App\Models\Provider;
use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Opportunity\Models\Opportunity;

class OpportunityAdminRejectedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(
        public Opportunity $opportunity,
        public string $reason,
    ) {}

    protected function titleKey(): string
    {
        return 'opportunity_admin_rejected';
    }

    protected function bodyKey(): string
    {
        return 'opportunity_has_been_admin_rejected';
    }

    protected function payload(): array
    {
        return [
            'opportunity_id' => $this->opportunity->id,
            'reason' => $this->reason,
        ];
    }

    protected function broadcastData(object $notifiable): array
    {
        return $this->firebaseData($notifiable);
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
        return $notifiable instanceof User || $notifiable instanceof Provider;
    }

    public function broadcastType(): string
    {
        return 'opportunity admin rejected';
    }
}
