<?php

namespace Modules\Classifieds\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Classifieds\Contracts\Repositories\CarAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\CarAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\QueryFilters\CarAdvisementFilters;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class CarAdvisementService
{
    public function __construct(
        private readonly CarAdvisementRepositoryInterface $repository,
    ) {}

    public function listUserAdvisements(User $user, CarAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getUserAdvisements($user, $filters);
    }

    public function listPublishedAdvisements(CarAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getPublishedAdvisements($filters);
    }

    public function create(User $user, CarAdvisementDTO $dto): CarAdvisement
    {
        return DB::transaction(function () use ($user, $dto): CarAdvisement {
            $carAdvisement = CarAdvisement::withoutEvents(function () use ($user, $dto): CarAdvisement {
                return $this->repository->create([
                    ...$dto->toPersistenceArray(),
                    'user_type' => $user::class,
                    'user_id' => $user->id,
                    'status' => AdvisementStatusEnum::PENDING,
                ]);
            });

            $this->storeMedia($carAdvisement, $dto);
            $carAdvisement->load([
                'carBrand',
                'carType',
                'carCategory',
                'city',
                'region',
                'user',
                'media',
            ]);

            return $carAdvisement;
        });
    }

    public function update(User $user, CarAdvisement $model, CarAdvisementDTO $dto): CarAdvisement
    {
        $this->authorizeOwner($user, $model);

        return DB::transaction(function () use ($model, $dto): CarAdvisement {
            $this->repository->update($model, $dto->toPersistenceArray());
            $this->storeMedia($model, $dto);
            $model->load([
                'carBrand',
                'carType',
                'carCategory',
                'city',
                'region',
                'user',
                'media',
            ]);

            return $model;
        });
    }

    public function delete(User $user, CarAdvisement $model): void
    {
        $this->authorizeOwner($user, $model);

        DB::transaction(function () use ($model): void {
            if (Schema::hasTable('media')) {
                $model->clearMediaCollection();
            }
            $model->delete();
        });
    }

    public function deleteMedia(User $user, CarAdvisement $model, Media $media): void
    {
        $this->authorizeOwner($user, $model);

        if (! Schema::hasTable('media') || $media->model_id !== $model->id || $media->model_type !== $model::class) {
            throw new AccessDeniedHttpException;
        }

        DB::transaction(function () use ($media): void {
            $media->delete();
        });
    }

    public function loadForShow(CarAdvisement $model): CarAdvisement
    {
        return $model->load([
            'carBrand',
            'carType',
            'carCategory',
            'city',
            'region',
            'user',
            'media',
        ]);
    }

    private function authorizeOwner(User $user, CarAdvisement $model): void
    {
        if ($model->user_id !== $user->id || $model->user_type !== $user::class) {
            throw new AccessDeniedHttpException;
        }
    }

    private function storeMedia(CarAdvisement $model, CarAdvisementDTO $dto): void
    {
        if (! $dto->files) {
            return;
        }

        foreach ($dto->files as $file) {
            $model->addMedia($file)
                ->toMediaCollection();
        }
    }
}
