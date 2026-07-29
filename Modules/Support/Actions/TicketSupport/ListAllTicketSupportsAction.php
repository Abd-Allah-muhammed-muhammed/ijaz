<?php

namespace Modules\Support\Actions\TicketSupport;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Support\Contracts\Repositories\TicketSupportRepositoryInterface;

class ListAllTicketSupportsAction
{
    public function __construct(
        private readonly TicketSupportRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateAll($request);
    }
}
