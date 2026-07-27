<?php

namespace Modules\Catalog\Actions\Specialization;

use Modules\Catalog\Concerns\HandlesTransactionalFileUpload;
use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;
use Modules\Catalog\DTOs\StoreSpecializationDTO;
use Modules\Catalog\Models\Specialization;
use Throwable;

class StoreSpecializationAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly SpecializationRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreSpecializationDTO $dto): Specialization
    {
        return $this->storeFileWithCleanup(
            file: $dto->icon,
            directory: 'specializations',
            disk: null,
            dbWork: function (?string $iconPath) use ($dto): Specialization {
                $specialization = $this->repository->create([
                    'parent_id' => $dto->parentId,
                    'icon' => $iconPath,
                ]);
                $specialization->translations()->createMany($dto->translations);

                return $specialization->load(['translation']);
            },
        );
    }
}
