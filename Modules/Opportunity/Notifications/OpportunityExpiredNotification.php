<?php

namespace Modules\Opportunity\Notifications;

use App\Models\Provider;
use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Opportunity\Models\Opportunity;

class OpportunityExpiredNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public Opportunity $opportunity) {}

    protected function titleKey(): string
    {
        return 'opportunity_expired';
    }

    protected function bodyKey(): string
    {
        return 'opportunity_has_expired';
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
        return $notifiable instanceof User || $notifiable instanceof Provider;
    }

    public function broadcastType(): string
    {
        return 'opportunity expired';
    }
}
