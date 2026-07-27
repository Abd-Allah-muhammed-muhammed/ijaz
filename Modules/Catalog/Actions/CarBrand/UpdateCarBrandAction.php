<?php

namespace Modules\Catalog\Actions\CarBrand;

use Modules\Catalog\Concerns\HandlesTransactionalFileUpload;
use Modules\Catalog\Contracts\Repositories\CarBrandRepositoryInterface;
use Modules\Catalog\DTOs\UpdateCarBrandDTO;
use Modules\Catalog\Models\CarBrand;
use Throwable;

class UpdateCarBrandAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly CarBrandRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CarBrand $carBrand, UpdateCarBrandDTO $dto): CarBrand
    {
        return $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'car_brands',
            disk: 'public',
            previousPath: $dto->image !== null ? $carBrand->image : null,
            dbWork: function (?string $imagePath) use ($dto, $carBrand): CarBrand {
                $data = [
                    'is_active' => $dto->isActive,
                ];

                if ($imagePath !== null) {
                    $data['image'] = $imagePath;
                }

                $carBrand = $this->repository->update($carBrand, $data);
                $carBrand->translations()->delete();
                $carBrand->translations()->createMany($dto->translations);

                return $carBrand->load(['translation']);
            },
        );
    }
}
