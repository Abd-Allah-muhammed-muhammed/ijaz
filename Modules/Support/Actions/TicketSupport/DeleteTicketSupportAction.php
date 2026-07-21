<?php

namespace Modules\Support\Actions\TicketSupport;

use Modules\Support\Contracts\Repositories\TicketSupportRepositoryInterface;
use Modules\Support\Exceptions\TicketSupportNotDeletableException;
use Modules\Support\Models\TicketSupport;

class DeleteTicketSupportAction
{
    public function __construct(
        private readonly TicketSupportRepositoryInterface $repository,
    ) {}

    /**
     * @throws TicketSupportNotDeletableException
     */
    public function handle(TicketSupport $ticket): void
    {
        $this->repository->delete($ticket);
    }
}
