<?php

namespace Modules\Support\Actions\TicketSupport;

use Modules\Support\Contracts\Repositories\TicketSupportRepositoryInterface;
use Modules\Support\DTOs\UpdateTicketSupportStatusDTO;
use Modules\Support\Models\TicketSupport;
use Modules\Support\Notifications\TicketStatusChangedNotification;

class UpdateTicketSupportStatusAction
{
    public function __construct(
        private readonly TicketSupportRepositoryInterface $repository,
    ) {}

    public function handle(TicketSupport $ticket, UpdateTicketSupportStatusDTO $dto): TicketSupport
    {
        $previousStatus = $ticket->status->value;
        $nextStatus = $dto->status->value;

        $ticket = $this->repository->updateStatus($ticket, $dto->status);

        if (
            $previousStatus !== $nextStatus
            && TicketStatusChangedNotification::shouldNotify($nextStatus)
            && $ticket->user !== null
        ) {
            $ticket->user->notify(new TicketStatusChangedNotification(
                ticket: $ticket,
                status: $nextStatus,
            ));
        }

        return $ticket;
    }
}
