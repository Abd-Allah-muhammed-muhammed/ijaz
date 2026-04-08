<?php

namespace App\Notifications\User;

use App\Models\Order;
use App\Services\Firebase\DTO\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderAcceptedOfferUpdatedNotification extends Notification implements ShouldBroadcastNow, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Order $order)
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
            'title_translated_key' => 'order_accepted_offer_updated',
            'body_translated_key' => 'the_order_accepted_offer_has_been_updated',
            'translated_attributes' => [],
            'order_id' => $this->order->id,
            'offer_id' => $this->order->accepted_offer_id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'title' => trans('order_accepted_offer_updated', locale: $notifiable->language),
            'body' => trans('the_order_accepted_offer_has_been_updated', locale: $notifiable->language),
            'order_id' => $this->order->id,
            'offer_id' => $this->order->accepted_offer_id,
        ]))->onConnection('sync');
    }

    public function broadcastType(): string
    {
        return 'new assigned order';
    }

    public function toFirebase(object $notifiable): Message
    {
        return new Message(
            title: trans('order_accepted_offer_updated', locale: $notifiable->language),
            body: trans('the_order_accepted_offer_has_been_updated', locale: $notifiable->language),
            data: ['order_id' => $this->order->id],
        );
    }
}
