<?php

namespace Modules\Support\Notifications;

use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Support\Models\TicketSupport;

/**
 * New support ticket created — Admin-facing creation event (not a status transition).
 * Mirrors ReviewReceivedNotification (DomainNotification, not StatusChangedNotification).
 */
class TicketCreatedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public TicketSupport $ticket) {}

    protected function titleKey(): string
    {
        return 'support_ticket_created_title';
    }

    protected function bodyKey(): string
    {
        return 'support_ticket_created_body';
    }

    protected function payload(): array
    {
        return [
            'ticket_support_id' => $this->ticket->id,
            'title' => $this->ticket->title,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'ticket_support_id' => $this->ticket->id,
            'screen' => 'supportTicket',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        // Admins have no device tokens / FCM registration on the web dashboard.
        return false;
    }

    public function broadcastType(): string
    {
        return 'support ticket created';
    }
}
