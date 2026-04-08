<?php

namespace App\Notifications\Provider;

use App\Models\OrderOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderOfferCanceledNotification extends Notification implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public OrderOffer $offer)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title_translated_key' => 'order_offer_canceled',
            'body_translated_key' => 'order_offer_has_been_canceled',
            'translated_attributes' => [],
            'order_id' => $this->offer->order_id,
            'offer_id' => $this->offer->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'title' => trans('order_offer_canceled', locale: $notifiable->language),
            'body' => trans('order_offer_has_been_canceled', locale: $notifiable->language),
            'order_id' => $this->offer->order_id,
            'offer_id' => $this->offer->id,
        ]))->onConnection('sync');
    }

    public function broadcastType(): string
    {
        return 'order offer canceled';
    }
}
