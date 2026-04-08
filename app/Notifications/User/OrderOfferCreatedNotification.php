<?php

namespace App\Notifications\User;

use App\Models\OrderOffer;
use App\Services\Firebase\DTO\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderOfferCreatedNotification extends Notification implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldQueue
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
        return ['database', 'broadcast', 'firebase'];
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
            'title_translated_key' => 'order_offer_created',
            'body_translated_key' => 'order_offer_has_been_created',
            'translated_attributes' => [],
            'order_id' => $this->offer->order_id,
            'offer_id' => $this->offer->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'title' => trans('order_offer_created', locale: $notifiable->language),
            'body' => trans('order_offer_has_been_created', locale: $notifiable->language),
            'order_id' => $this->offer->order_id,
            'offer_id' => $this->offer->id,
        ]))->onConnection('sync');
    }

    public function broadcastType(): string
    {
        return 'order offer created';
    }

    public function toFirebase(object $notifiable): Message
    {
        return new Message(
            title: trans('order_offer_created', locale: $notifiable->language),
            body: trans('order_offer_has_been_created', locale: $notifiable->language),
            data: ['order_id' => $this->offer->order_id],
        );
    }
}
