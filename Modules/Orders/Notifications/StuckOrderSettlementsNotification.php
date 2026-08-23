<?php

namespace Modules\Orders\Notifications;

use App\Models\Admin;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Notifications\Messages\BroadcastMessage;

class StuckOrderSettlementsNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public int $stuckCount) {}

    protected function titleKey(): string
    {
        return 'stuck_order_settlements_title';
    }

    protected function bodyKey(): string
    {
        return 'stuck_order_settlements_body';
    }

    protected function payload(): array
    {
        return [
            'stuck_count' => $this->stuckCount,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'stuck_count' => (string) $this->stuckCount,
            'screen' => 'orders',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof Admin;
    }

    public function broadcastType(): string
    {
        return 'stuck order settlements';
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $replace = ['count' => $this->stuckCount];

        return (new BroadcastMessage([
            'title' => trans($this->titleKey(), $replace, $notifiable->language),
            'body' => trans($this->bodyKey(), $replace, $notifiable->language),
            ...$this->broadcastData($notifiable),
        ]))->onConnection('sync');
    }
}
