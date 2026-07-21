<?php

namespace Modules\Cms\Actions\Banner;

use Illuminate\Support\Facades\DB;
use Modules\Cms\Contracts\Repositories\BannerRepositoryInterface;
use Modules\Cms\DTOs\UpdateBannerDTO;
use Modules\Cms\Models\Banner;
use Throwable;

class UpdateBannerAction
{
    public function __construct(
        private readonly BannerRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Banner $banner, UpdateBannerDTO $dto): Banner
    {
        return DB::transaction(function () use ($banner, $dto): Banner {
            $data = ['link' => $dto->link];

            if ($dto->image !== null) {
                $banner->deleteImage();
                $data['image'] = $dto->image->store('banners', 'public');
            }

            return $this->repository->update($banner, $data);
        });
    }
}
