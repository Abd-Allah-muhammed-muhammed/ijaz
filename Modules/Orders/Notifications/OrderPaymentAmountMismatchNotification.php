<?php

namespace Modules\Orders\Notifications;

use App\Models\User;
use App\Notifications\DomainNotification;
use App\Services\Firebase\DTO\FirebaseNotificationContent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;

class OrderPaymentAmountMismatchNotification extends DomainNotification implements ShouldBroadcastNow
{
    public function __construct(
        public Order $order,
        public OrderOffer $offer,
        public float $paidAmount,
        public float $expectedTotal,
    ) {}

    protected function titleKey(): string
    {
        return 'order_payment_amount_mismatch';
    }

    protected function bodyKey(): string
    {
        return 'order_payment_amount_mismatch_body';
    }

    /**
     * @return array<string, mixed>
     */
    protected function translationReplacements(): array
    {
        return [
            'paid_amount' => $this->formatAmount($this->paidAmount),
            'expected_total' => $this->formatAmount($this->expectedTotal),
        ];
    }

    protected function payload(): array
    {
        return [
            'order_id' => $this->order->id,
            'offer_id' => $this->offer->id,
            'paid_amount' => $this->formatAmount($this->paidAmount),
            'expected_total' => $this->formatAmount($this->expectedTotal),
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
            'offer_id' => $this->offer->id,
            'paid_amount' => $this->formatAmount($this->paidAmount),
            'expected_total' => $this->formatAmount($this->expectedTotal),
            'screen' => 'orders',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User;
    }

    public function broadcastType(): string
    {
        return 'order payment amount mismatch';
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
