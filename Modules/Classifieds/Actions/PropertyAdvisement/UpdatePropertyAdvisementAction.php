<?php

namespace Modules\Classifieds\Actions\PropertyAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\PropertyAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\PropertyAdvisementDTO;
use Modules\Classifieds\Models\PropertyAdvisement;

final class UpdatePropertyAdvisementAction
{
    public function __construct(
        private readonly PropertyAdvisementRepositoryInterface $repository,
        private readonly StoreAdvisementMediaAction $storeAdvisementMediaAction,
    ) {}

    public function handle(PropertyAdvisement $model, PropertyAdvisementDTO $dto): PropertyAdvisement
    {
        return DB::transaction(function () use ($model, $dto): PropertyAdvisement {
            $this->repository->update($model, $dto->toPersistenceArray());
            $this->storeAdvisementMediaAction->handle($model, $dto->files);
            // Keep `.translation` — see CreatePropertyAdvisementAction comment.
            $model->load([
                'propertyType.translation',
                'city.translation',
                'region.translation',
                'category.translation',
                'user',
                'media',
            ]);

            return $model;
        });
    }
}
