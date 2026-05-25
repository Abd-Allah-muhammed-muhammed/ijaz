<?php

namespace Modules\Classifieds\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Classifieds\Contracts\Repositories\InstituteAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\InstituteAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\QueryFilters\InstituteAdvisementFilters;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class InstituteAdvisementService
{
    public function __construct(
        private readonly InstituteAdvisementRepositoryInterface $repository,
    ) {}

    public function listUserAdvisements(User $user, InstituteAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getUserAdvisements($user, $filters);
    }

    public function listPublishedAdvisements(InstituteAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getPublishedAdvisements($filters);
    }

    public function create(User $user, InstituteAdvisementDTO $dto): InstituteAdvisement
    {
        return DB::transaction(function () use ($user, $dto): InstituteAdvisement {
            $instituteAdvisement = InstituteAdvisement::withoutEvents(function () use ($user, $dto): InstituteAdvisement {
                return $this->repository->create([
                    ...$dto->toPersistenceArray(),
                    'user_type' => $user::class,
                    'user_id' => $user->id,
                    'status' => AdvisementStatusEnum::PENDING,
                ]);
            });

            $this->storeMedia($instituteAdvisement, $dto);
            $instituteAdvisement->load([
                'specialization',
                'city',
                'region',
                'user',
                'media',
            ]);

            return $instituteAdvisement;
        });
    }

    public function update(User $user, InstituteAdvisement $model, InstituteAdvisementDTO $dto): InstituteAdvisement
    {
        $this->authorizeOwner($user, $model);

        return DB::transaction(function () use ($model, $dto): InstituteAdvisement {
            $this->repository->update($model, $dto->toPersistenceArray());
            $this->storeMedia($model, $dto);
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

    public function delete(User $user, InstituteAdvisement $model): void
    {
        $this->authorizeOwner($user, $model);

        DB::transaction(function () use ($model): void {
            if (Schema::hasTable('media')) {
                $model->clearMediaCollection();
            }
            $model->delete();
        });
    }

    public function deleteMedia(User $user, InstituteAdvisement $model, Media $media): void
    {
        $this->authorizeOwner($user, $model);

        if (! Schema::hasTable('media') || $media->model_id !== $model->id || $media->model_type !== $model::class) {
            throw new AccessDeniedHttpException;
        }

        DB::transaction(function () use ($media): void {
            $media->delete();
        });
    }

    public function loadForShow(InstituteAdvisement $model): InstituteAdvisement
    {
        return $model->load([
            'specialization',
            'city',
            'region',
            'user',
            'media',
        ]);
    }

    private function authorizeOwner(User $user, InstituteAdvisement $model): void
    {
        if ($model->user_id !== $user->id || $model->user_type !== $user::class) {
            throw new AccessDeniedHttpException;
        }
    }

    private function storeMedia(InstituteAdvisement $model, InstituteAdvisementDTO $dto): void
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
