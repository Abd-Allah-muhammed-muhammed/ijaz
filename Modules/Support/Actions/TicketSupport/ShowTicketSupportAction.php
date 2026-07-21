<?php

namespace Modules\Support\Actions\TicketSupport;

use Modules\Support\Contracts\Repositories\TicketSupportRepositoryInterface;
use Modules\Support\Models\TicketSupport;

class ShowTicketSupportAction
{
    public function __construct(
        private readonly TicketSupportRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<int, string>  $relations
     */
    public function handle(TicketSupport $ticket, array $relations = []): TicketSupport
    {
        if ($relations === []) {
            return $ticket;
        }

        return $this->repository->loadForShow($ticket, $relations);
    }
}
