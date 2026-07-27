<?php

namespace Modules\Catalog\Actions\Specialization;

use Modules\Catalog\Concerns\HandlesTransactionalFileUpload;
use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;
use Modules\Catalog\DTOs\UpdateSpecializationDTO;
use Modules\Catalog\Models\Specialization;
use Throwable;

class UpdateSpecializationAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly SpecializationRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Specialization $specialization, UpdateSpecializationDTO $dto): Specialization
    {
        return $this->storeFileWithCleanup(
            file: $dto->icon,
            directory: 'specializations',
            disk: null,
            previousPath: $dto->icon !== null ? $specialization->icon : null,
            dbWork: function (?string $iconPath) use ($dto, $specialization): Specialization {
                $data = [
                    'parent_id' => $dto->parentId,
                ];

                if ($iconPath !== null) {
                    $data['icon'] = $iconPath;
                }

                $specialization = $this->repository->update($specialization, $data);
                $specialization->translations()->delete();
                $specialization->translations()->createMany($dto->translations);

                return $specialization->load(['translation']);
            },
        );
    }
}
