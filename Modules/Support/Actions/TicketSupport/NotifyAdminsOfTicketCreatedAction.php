<?php

namespace Modules\Support\Actions\TicketSupport;

use App\Contracts\Auth\AdminRepositoryInterface;
use Illuminate\Support\Facades\Notification;
use Modules\Support\Models\TicketSupport;
use Modules\Support\Notifications\TicketCreatedNotification;

/**
 * Fans out TicketCreatedNotification to Admins who can view support tickets
 * (`show supportTicket`), matching dashboard SupportController authorization —
 * not every staff Admin.
 */
class NotifyAdminsOfTicketCreatedAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function handle(TicketSupport $ticket): void
    {
        $admins = $this->adminRepository->getWithPermission('show supportTicket');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new TicketCreatedNotification($ticket));
    }
}
