<?php

namespace Modules\Support\Notifications;

use App\Notifications\StatusChangedNotification;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Models\TicketSupport;

/**
 * TicketSupportStatusEnum has Pending / Open / Closed — there is no "resolved" case.
 * Only Closed is treated as a final, notify-worthy transition.
 */
class TicketStatusChangedNotification extends StatusChangedNotification
{
    public function __construct(
        public TicketSupport $ticket,
        public string $status,
    ) {}

    /**
     * @return list<string>
     */
    public static function notifiableStatuses(): array
    {
        return [
            TicketSupportStatusEnum::Closed->value,
        ];
    }

    public static function shouldNotify(string $status): bool
    {
        return in_array($status, self::notifiableStatuses(), true);
    }

    protected function domain(): string
    {
        return 'support_ticket';
    }

    protected function statusValue(): string
    {
        return $this->status;
    }

    protected function entityPayload(): array
    {
        return [
            'ticket_support_id' => $this->ticket->id,
        ];
    }

    protected function entityFirebaseData(object $notifiable): array
    {
        return [
            'ticket_support_id' => $this->ticket->id,
            'screen' => 'supportTicket',
        ];
    }
}
