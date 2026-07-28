<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use App\Support\HandlesTransactionalFileUpload;
use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\DTOs\UpdateElectronicBrandDTO;
use Modules\Catalog\Models\ElectronicBrand;
use Throwable;

class UpdateElectronicBrandAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly ElectronicBrandRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(ElectronicBrand $electronicBrand, UpdateElectronicBrandDTO $dto): ElectronicBrand
    {
        return $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'electronic_brands',
            disk: 'public',
            previousPath: $dto->image !== null ? $electronicBrand->image : null,
            dbWork: function (?string $imagePath) use ($dto, $electronicBrand): ElectronicBrand {
                $data = [
                    'is_active' => $dto->isActive,
                ];

                if ($imagePath !== null) {
                    $data['image'] = $imagePath;
                }

                $electronicBrand = $this->repository->update($electronicBrand, $data);
                $electronicBrand->translations()->delete();
                $electronicBrand->translations()->createMany($dto->translations);

                return $electronicBrand->load(['translation']);
            },
        );
    }
}
