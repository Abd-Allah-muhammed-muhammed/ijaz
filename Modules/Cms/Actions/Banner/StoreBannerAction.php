<?php

namespace Modules\Cms\Actions\Banner;

use App\Support\HandlesTransactionalFileUpload;
use App\Support\LookupCache;
use Modules\Cms\Contracts\Repositories\BannerRepositoryInterface;
use Modules\Cms\DTOs\StoreBannerDTO;
use Modules\Cms\Models\Banner;
use Throwable;

class StoreBannerAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly BannerRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreBannerDTO $dto): Banner
    {
        $banner = $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'banners',
            disk: 'public',
            dbWork: fn (?string $imagePath): Banner => $this->repository->create([
                'link' => $dto->link,
                'image' => $imagePath,
            ]),
        );

        LookupCache::forget('banners:all');

        return $banner;
    }
}
