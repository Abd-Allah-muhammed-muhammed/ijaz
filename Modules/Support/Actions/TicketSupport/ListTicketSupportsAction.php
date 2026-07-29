<?php

namespace Modules\Support\Actions\TicketSupport;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Support\Contracts\Repositories\TicketSupportRepositoryInterface;

class ListTicketSupportsAction
{
    public function __construct(
        private readonly TicketSupportRepositoryInterface $repository,
    ) {}

    public function handle(Model $user, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($user, $perPage);
    }
}
