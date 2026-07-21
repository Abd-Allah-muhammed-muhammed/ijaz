<?php

namespace Modules\Cms\Actions\Banner;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Cms\Contracts\Repositories\BannerRepositoryInterface;

class ListBannersAction
{
    public function __construct(
        private readonly BannerRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
