<?php

namespace Modules\Support\Actions\TicketSupport;

use Modules\Support\Contracts\Repositories\TicketSupportRepositoryInterface;
use Modules\Support\DTOs\UpdateTicketSupportStatusDTO;
use Modules\Support\Models\TicketSupport;

class UpdateTicketSupportStatusAction
{
    public function __construct(
        private readonly TicketSupportRepositoryInterface $repository,
    ) {}

    public function handle(TicketSupport $ticket, UpdateTicketSupportStatusDTO $dto): TicketSupport
    {
        return $this->repository->updateStatus($ticket, $dto->status);
    }
}
