<?php

namespace Modules\Orders\Notifications;

use App\Notifications\DomainNotification;
use App\Services\Firebase\DTO\FirebaseNotificationContent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Orders\Models\Order;

class OrderAcceptedOfferPriceDecreasedNotification extends DomainNotification implements ShouldBroadcastNow
{
    public function __construct(
        public Order $order,
        public float $oldPrice,
        public float $newPrice,
    ) {}

    protected function titleKey(): string
    {
        return 'order_accepted_offer_price_decreased';
    }

    protected function bodyKey(): string
    {
        return 'order_accepted_offer_price_decreased_body';
    }

    /**
     * @return array<string, mixed>
     */
    protected function translationReplacements(): array
    {
        return [
            'old_price' => $this->formatPrice($this->oldPrice),
            'new_price' => $this->formatPrice($this->newPrice),
        ];
    }

    protected function payload(): array
    {
        return [
            'order_id' => $this->order->id,
            'offer_id' => $this->order->accepted_offer_id,
            'old_price' => $this->formatPrice($this->oldPrice),
            'new_price' => $this->formatPrice($this->newPrice),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title_translated_key' => $this->titleKey(),
            'body_translated_key' => $this->bodyKey(),
            'translated_attributes' => $this->translationReplacements(),
            ...$this->payload(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'title' => trans($this->titleKey(), $this->translationReplacements(), locale: $notifiable->language),
            'body' => trans($this->bodyKey(), $this->translationReplacements(), locale: $notifiable->language),
            ...$this->broadcastData($notifiable),
        ]))->onConnection('sync');
    }

    public function toFirebase(object $notifiable): FirebaseNotificationContent
    {
        return FirebaseNotificationContent::make(
            title: trans($this->titleKey(), $this->translationReplacements(), locale: $notifiable->language),
            body: trans($this->bodyKey(), $this->translationReplacements(), locale: $notifiable->language),
            data: $this->firebaseData($notifiable),
        );
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'offer_id' => $this->order->accepted_offer_id,
            'old_price' => $this->formatPrice($this->oldPrice),
            'new_price' => $this->formatPrice($this->newPrice),
            'screen' => 'orders',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return true;
    }

    public function broadcastType(): string
    {
        return 'new assigned order';
    }

    private function formatPrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }
}
