<?php

namespace Modules\Opportunity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Opportunity\Models\OpportunityOffer;

class OpportunityOfferRejectedNotification extends Notification implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldQueue
{
    use Queueable;

    public function __construct(public OpportunityOffer $offer) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title_translated_key' => 'opportunity_offer_rejected',
            'body_translated_key' => 'opportunity_offer_has_been_rejected',
            'translated_attributes' => [],
            'opportunity_id' => $this->offer->opportunity_id,
            'offer_id' => $this->offer->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'title' => trans($this->toArray($notifiable)['title_translated_key'], locale: $notifiable->language),
            'body' => trans($this->toArray($notifiable)['body_translated_key'], locale: $notifiable->language),
            'opportunity_id' => $this->offer->opportunity_id,
            'offer_id' => $this->offer->id,
        ]))->onConnection('sync');
    }

    public function broadcastType(): string
    {
        return 'opportunity offer rejected';
    }
}
