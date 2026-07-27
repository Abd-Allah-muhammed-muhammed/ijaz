<?php

namespace Modules\Catalog\Actions\CarType;

use Modules\Catalog\Concerns\HandlesTransactionalFileUpload;
use Modules\Catalog\Contracts\Repositories\CarTypeRepositoryInterface;
use Modules\Catalog\DTOs\StoreCarTypeDTO;
use Modules\Catalog\Models\CarType;
use Throwable;

class StoreCarTypeAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly CarTypeRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreCarTypeDTO $dto): CarType
    {
        return $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'car_types',
            disk: 'public',
            dbWork: function (?string $imagePath) use ($dto): CarType {
                $carType = $this->repository->create([
                    'is_active' => $dto->isActive,
                    'image' => $imagePath,
                    'car_brand_id' => $dto->carBrandId,
                ]);
                $carType->translations()->createMany($dto->translations);

                return $carType->load(['translation', 'carBrand.translation']);
            },
        );
    }
}
