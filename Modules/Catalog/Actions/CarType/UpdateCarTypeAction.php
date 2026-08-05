<?php

namespace Modules\Catalog\Actions\CarType;

use App\Support\HandlesTransactionalFileUpload;
use App\Support\LookupCache;
use Modules\Catalog\Contracts\Repositories\CarTypeRepositoryInterface;
use Modules\Catalog\DTOs\UpdateCarTypeDTO;
use Modules\Catalog\Models\CarType;
use Throwable;

class UpdateCarTypeAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly CarTypeRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CarType $carType, UpdateCarTypeDTO $dto): CarType
    {
        $previousBrandId = (int) $carType->car_brand_id;

        $carType = $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'car_types',
            disk: 'public',
            previousPath: $dto->image !== null ? $carType->image : null,
            dbWork: function (?string $imagePath) use ($dto, $carType): CarType {
                $data = [
                    'is_active' => $dto->isActive,
                    'car_brand_id' => $dto->carBrandId,
                ];

                if ($imagePath !== null) {
                    $data['image'] = $imagePath;
                }

                $carType = $this->repository->update($carType, $data);
                $carType->translations()->delete();
                $carType->translations()->createMany($dto->translations);

                return $carType->load(['translation', 'carBrand.translation']);
            },
        );

        LookupCache::forgetScopedAllLocales('car-types:by-brand', $previousBrandId);
        LookupCache::forgetScopedAllLocales('car-types:by-brand', $dto->carBrandId);
        LookupCache::forgetScopedAllLocales('car-types:by-brand', 0);

        return $carType;
    }
}
