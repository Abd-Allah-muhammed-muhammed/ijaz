<?php

namespace Modules\Classifieds\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Classifieds\Actions\ElectronicAdvisement\ListElectronicAdvisementsForDashboardAction;
use Modules\Classifieds\Actions\ElectronicAdvisement\ResolveElectronicAdvisementDashboardSelectsAction;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\ElectronicAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\ElectronicAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\QueryFilters\ElectronicAdvisementFilters;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ElectronicAdvisementService
{
    public function __construct(
        private readonly ElectronicAdvisementRepositoryInterface $repository,
        private readonly ListElectronicAdvisementsForDashboardAction $listForDashboardAction,
        private readonly ResolveElectronicAdvisementDashboardSelectsAction $resolveDashboardSelectsAction,
        private readonly StoreAdvisementMediaAction $storeAdvisementMediaAction,
    ) {}

    public function listForDashboard(Request $request): LengthAwarePaginator
    {
        return $this->listForDashboardAction->handle($request);
    }

    /**
     * @return array{status: array{value: string, label: string, color: string}|null, condition: array{value: string, label: string, color: string}|null, device_category: array{value: int, label: string}|null, electronic_brand: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null}
     */
    public function resolveDashboardSelects(Request $request): array
    {
        return $this->resolveDashboardSelectsAction->handle($request);
    }

    public function listUserAdvisements(User $user, ElectronicAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getUserAdvisements($user, $filters);
    }

    public function listPublishedAdvisements(ElectronicAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getPublishedAdvisements($filters);
    }

    public function create(User $user, ElectronicAdvisementDTO $dto): ElectronicAdvisement
    {
        return DB::transaction(function () use ($user, $dto): ElectronicAdvisement {
            $electronicAdvisement = $this->repository->create([
                ...$dto->toPersistenceArray(),
                'user_type' => $user::class,
                'user_id' => $user->id,
                'status' => AdvisementStatusEnum::PENDING,
            ]);

            $this->storeAdvisementMediaAction->handle($electronicAdvisement, $dto->files);
            $electronicAdvisement->load([
                'deviceCategory',
                'electronicBrand',
                'city',
                'region',
                'user',
                'media',
            ]);

            return $electronicAdvisement;
        });
    }

    public function update(User $user, ElectronicAdvisement $model, ElectronicAdvisementDTO $dto): ElectronicAdvisement
    {
        $this->authorizeOwner($user, $model);

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

    public function delete(User $user, ElectronicAdvisement $model): void
    {
        $this->authorizeOwner($user, $model);

        DB::transaction(function () use ($model): void {
            if (Schema::hasTable('media')) {
                $model->clearMediaCollection();
            }
            $model->delete();
        });
    }

    public function deleteMedia(User $user, ElectronicAdvisement $model, Media $media): void
    {
        $this->authorizeOwner($user, $model);

        if (! Schema::hasTable('media') || $media->model_id !== $model->id || $media->model_type !== $model::class) {
            throw new AccessDeniedHttpException;
        }

        DB::transaction(function () use ($media): void {
            $media->delete();
        });
    }

    public function loadForShow(ElectronicAdvisement $model): ElectronicAdvisement
    {
        return $model->load([
            'deviceCategory',
            'electronicBrand',
            'city',
            'region',
            'user',
            'media',
        ]);
    }

    private function authorizeOwner(User $user, ElectronicAdvisement $model): void
    {
        if ($model->user_id !== $user->id || $model->user_type !== $user::class) {
            throw new AccessDeniedHttpException;
        }
    }
}
