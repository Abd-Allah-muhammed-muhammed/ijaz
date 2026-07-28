<?php

namespace Modules\Classifieds\Actions\ElectronicAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\ElectronicAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\ElectronicAdvisementDTO;
use Modules\Classifieds\Models\ElectronicAdvisement;

final class UpdateElectronicAdvisementAction
{
    public function __construct(
        private readonly ElectronicAdvisementRepositoryInterface $repository,
        private readonly StoreAdvisementMediaAction $storeAdvisementMediaAction,
    ) {}

    public function handle(ElectronicAdvisement $model, ElectronicAdvisementDTO $dto): ElectronicAdvisement
    {
        return DB::transaction(function () use ($model, $dto): ElectronicAdvisement {
            $this->repository->update($model, $dto->toPersistenceArray());
            $this->storeAdvisementMediaAction->handle($model, $dto->files);
            $model->load([
                'deviceCategory',
                'electronicBrand',
                'city',
                'region',
                'user',
                'media',
            ]);

            return $model;
        });
    }
}
