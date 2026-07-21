<?php

namespace Modules\Support\Actions\TicketSupport;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Support\Contracts\Repositories\TicketSupportRepositoryInterface;

class ListAllTicketSupportsAction
{
    public function __construct(
        private readonly TicketSupportRepositoryInterface $repository,
    ) {}

    public function handle(int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginateAll($perPage);
    }
}
