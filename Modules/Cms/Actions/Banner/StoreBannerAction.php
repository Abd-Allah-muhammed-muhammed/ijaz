<?php

namespace Modules\Cms\Actions\Banner;

use Illuminate\Support\Facades\DB;
use Modules\Cms\Contracts\Repositories\BannerRepositoryInterface;
use Modules\Cms\DTOs\StoreBannerDTO;
use Modules\Cms\Models\Banner;
use Throwable;

class StoreBannerAction
{
    public function __construct(
        private readonly BannerRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreBannerDTO $dto): Banner
    {
        return DB::transaction(function () use ($dto): Banner {
            return $this->repository->create([
                'link' => $dto->link,
                'image' => $dto->image->store('banners', 'public'),
            ]);
        });
    }
}
