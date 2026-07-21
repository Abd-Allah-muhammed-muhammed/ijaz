<?php

namespace Modules\Cms\Actions\Message;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Cms\Contracts\Repositories\MessageRepositoryInterface;

class ListMessagesAction
{
    public function __construct(
        private readonly MessageRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
