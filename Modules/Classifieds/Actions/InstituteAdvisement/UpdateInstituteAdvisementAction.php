<?php

namespace Modules\Classifieds\Actions\InstituteAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\InstituteAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\InstituteAdvisementDTO;
use Modules\Classifieds\Models\InstituteAdvisement;

final class UpdateInstituteAdvisementAction
{
    public function __construct(
        private readonly InstituteAdvisementRepositoryInterface $repository,
        private readonly StoreAdvisementMediaAction $storeAdvisementMediaAction,
    ) {}

    public function handle(InstituteAdvisement $model, InstituteAdvisementDTO $dto): InstituteAdvisement
    {
        return DB::transaction(function () use ($model, $dto): InstituteAdvisement {
            $this->repository->update($model, $dto->toPersistenceArray());
            $this->storeAdvisementMediaAction->handle($model, $dto->files);
            $model->load([
                'specialization',
                'city',
                'region',
                'user',
                'media',
            ]);

            return $model;
        });
    }
}
