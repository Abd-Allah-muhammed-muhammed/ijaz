<?php

namespace Modules\Opportunity\Notifications;

use App\Models\Provider;
use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Opportunity\Models\OpportunityOffer;

class OpportunityOfferSubmittedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public OpportunityOffer $offer) {}

    protected function titleKey(): string
    {
        return 'opportunity_offer_submitted';
    }

    protected function bodyKey(): string
    {
        return 'opportunity_offer_has_been_submitted';
    }

    protected function payload(): array
    {
        return [
            'opportunity_id' => $this->offer->opportunity_id,
            'offer_id' => $this->offer->id,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'opportunity_id' => $this->offer->opportunity_id,
            'offer_id' => $this->offer->id,
            'screen' => 'opportunity',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User || $notifiable instanceof Provider;
    }

    public function broadcastType(): string
    {
        return 'opportunity offer submitted';
    }
}
