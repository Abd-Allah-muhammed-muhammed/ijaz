<?php

namespace Modules\Opportunity\Notifications;

use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Opportunity\Models\OpportunityOffer;

class OpportunityOfferAcceptedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public OpportunityOffer $offer) {}

    protected function titleKey(): string
    {
        return 'opportunity_offer_accepted';
    }

    protected function bodyKey(): string
    {
        return 'opportunity_offer_has_been_accepted';
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
        return [];
    }

    public function broadcastType(): string
    {
        return 'opportunity offer accepted';
    }
}
