<?php

namespace Modules\Cms\Actions\Banner;

use Illuminate\Database\Eloquent\Collection;
use Modules\Cms\Contracts\Repositories\BannerRepositoryInterface;
use Modules\Cms\Models\Banner;

class GetAllBannersAction
{
    public function __construct(
        private readonly BannerRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Banner>
     */
    public function handle(): Collection
    {
        return $this->repository->all();
    }
}
