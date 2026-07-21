<?php

namespace Modules\Cms\Actions\Page;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Cms\Contracts\Repositories\PageRepositoryInterface;

class ListPagesAction
{
    public function __construct(
        private readonly PageRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
