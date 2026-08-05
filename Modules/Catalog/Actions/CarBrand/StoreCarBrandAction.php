<?php

namespace Modules\Catalog\Actions\CarBrand;

use App\Support\HandlesTransactionalFileUpload;
use App\Support\LookupCache;
use Modules\Catalog\Contracts\Repositories\CarBrandRepositoryInterface;
use Modules\Catalog\DTOs\StoreCarBrandDTO;
use Modules\Catalog\Models\CarBrand;
use Throwable;

class StoreCarBrandAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly CarBrandRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreCarBrandDTO $dto): CarBrand
    {
        $carBrand = $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'car_brands',
            disk: 'public',
            dbWork: function (?string $imagePath) use ($dto): CarBrand {
                $carBrand = $this->repository->create([
                    'is_active' => $dto->isActive,
                    'image' => $imagePath,
                ]);
                $carBrand->translations()->createMany($dto->translations);

                return $carBrand->load(['translation']);
            },
        );

        LookupCache::forgetAllLocales('car-brands:all');

        return $carBrand;
    }
}
