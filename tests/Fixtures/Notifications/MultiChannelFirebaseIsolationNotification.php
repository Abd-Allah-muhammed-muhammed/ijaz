<?php

namespace Tests\Fixtures\Notifications;

use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Concrete DomainNotification for Firebase multi-channel isolation tests.
 * Anonymous classes cannot be serialized by the broadcast channel.
 */
class MultiChannelFirebaseIsolationNotification extends DomainNotification implements ShouldBroadcastNow
{
    /**
     * @param  list<string>|null  $viaOrder
     */
    public function __construct(public readonly ?array $viaOrder = null) {}

    protected function titleKey(): string
    {
        return 'order_offer_created';
    }

    protected function bodyKey(): string
    {
        return 'order_offer_has_been_created';
    }

    protected function payload(): array
    {
        return ['order_id' => 99];
    }

    protected function firebaseData(object $notifiable): array
    {
        return ['order_id' => 99];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return true;
    }

    public function broadcastType(): string
    {
        return 'order offer created';
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->viaOrder ?? parent::via($notifiable);
    }
}
