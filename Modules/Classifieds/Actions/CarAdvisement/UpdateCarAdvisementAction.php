<?php

namespace Modules\Classifieds\Actions\CarAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\CarAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\CarAdvisementDTO;
use Modules\Classifieds\Models\CarAdvisement;

final class UpdateCarAdvisementAction
{
    public function __construct(
        private readonly CarAdvisementRepositoryInterface $repository,
        private readonly StoreAdvisementMediaAction $storeAdvisementMediaAction,
    ) {}

    public function handle(CarAdvisement $model, CarAdvisementDTO $dto): CarAdvisement
    {
        return DB::transaction(function () use ($model, $dto): CarAdvisement {
            $this->repository->update($model, $dto->toPersistenceArray());
            $this->storeAdvisementMediaAction->handle($model, $dto->files);
            $model->load([
                'carBrand',
                'carType',
                'carCategory',
                'city',
                'region',
                'bank.translations',
                'bank.media',
                'user',
                'media',
            ]);

            return $model;
        });
    }
}
