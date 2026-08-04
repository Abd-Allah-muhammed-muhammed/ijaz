<?php

namespace Modules\Cms\Actions\Banner;

use App\Support\HandlesTransactionalFileUpload;
use App\Support\LookupCache;
use Modules\Cms\Contracts\Repositories\BannerRepositoryInterface;
use Modules\Cms\DTOs\UpdateBannerDTO;
use Modules\Cms\Models\Banner;
use Throwable;

class UpdateBannerAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly BannerRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Banner $banner, UpdateBannerDTO $dto): Banner
    {
        $banner = $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'banners',
            disk: 'public',
            previousPath: $dto->image !== null ? $banner->image : null,
            dbWork: function (?string $imagePath) use ($banner, $dto): Banner {
                $data = ['link' => $dto->link];

                if ($imagePath !== null) {
                    $data['image'] = $imagePath;
                }

                return $this->repository->update($banner, $data);
            },
        );

        LookupCache::forget('banners:all');

        return $banner;
    }
}
