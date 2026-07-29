<?php

namespace Modules\Marketplace\Actions\ProviderType;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Marketplace\Contracts\Repositories\ProviderTypeRepositoryInterface;

class ListProviderTypesAction
{
    public function __construct(
        private readonly ProviderTypeRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForDashboard($request);
    }
}
