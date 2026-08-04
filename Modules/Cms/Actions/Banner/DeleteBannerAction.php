<?php

namespace Modules\Cms\Actions\Banner;

use App\Support\LookupCache;
use Modules\Cms\Contracts\Repositories\BannerRepositoryInterface;
use Modules\Cms\Models\Banner;

class DeleteBannerAction
{
    public function __construct(
        private readonly BannerRepositoryInterface $repository,
    ) {}

    public function handle(Banner $banner): void
    {
        $this->repository->delete($banner);

        LookupCache::forget('banners:all');
    }
}
