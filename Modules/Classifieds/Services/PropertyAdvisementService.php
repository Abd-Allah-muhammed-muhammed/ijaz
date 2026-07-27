<?php

namespace Modules\Classifieds\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Classifieds\Actions\PropertyAdvisement\ListPropertyAdvisementsForDashboardAction;
use Modules\Classifieds\Actions\PropertyAdvisement\ResolvePropertyAdvisementDashboardSelectsAction;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\PropertyAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\PropertyAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Classifieds\QueryFilters\PropertyAdvisementFilters;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class PropertyAdvisementService
{
    public function __construct(
        private readonly PropertyAdvisementRepositoryInterface $repository,
        private readonly ListPropertyAdvisementsForDashboardAction $listForDashboardAction,
        private readonly ResolvePropertyAdvisementDashboardSelectsAction $resolveDashboardSelectsAction,
        private readonly StoreAdvisementMediaAction $storeAdvisementMediaAction,
    ) {}

    public function listForDashboard(Request $request): LengthAwarePaginator
    {
        return $this->listForDashboardAction->handle($request);
    }

    /**
     * @return array{property_type: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null, category: array{value: int, label: string}|null}
     */
    public function resolveDashboardSelects(Request $request): array
    {
        return $this->resolveDashboardSelectsAction->handle($request);
    }

    public function listUserAdvisements(User $user, PropertyAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getUserAdvisements($user, $filters);
    }

    public function listPublishedAdvisements(PropertyAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getPublishedAdvisements($filters);
    }

    public function create(User $user, PropertyAdvisementDTO $dto): PropertyAdvisement
    {
        return DB::transaction(function () use ($user, $dto): PropertyAdvisement {
            // Do NOT wrap in Model::withoutEvents — HasNormalizedAttributes relies on
            // the saving event. Car/Electronic/Institute currently suppress it (latent bug).
            $propertyAdvisement = $this->repository->create([
                ...$dto->toPersistenceArray(),
                'user_type' => $user::class,
                'user_id' => $user->id,
                'status' => AdvisementStatusEnum::PENDING,
            ]);

            $this->storeAdvisementMediaAction->handle($propertyAdvisement, $dto->files);
            // Keep `.translation` — CityResource/RegionResource only expose title when
            // translation is loaded; PropertyType/Category are Astrotomic Translatable.
            $propertyAdvisement->load([
                'propertyType.translation',
                'city.translation',
                'region.translation',
                'category.translation',
                'user',
                'media',
            ]);

            return $propertyAdvisement;
        });
    }

    public function update(User $user, PropertyAdvisement $model, PropertyAdvisementDTO $dto): PropertyAdvisement
    {
        $this->authorizeOwner($user, $model);

        return DB::transaction(function () use ($model, $dto): PropertyAdvisement {
            $this->repository->update($model, $dto->toPersistenceArray());
            $this->storeAdvisementMediaAction->handle($model, $dto->files);
            // Keep `.translation` — see create() comment.
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

    public function delete(User $user, PropertyAdvisement $model): void
    {
        $this->authorizeOwner($user, $model);

        DB::transaction(function () use ($model): void {
            if (Schema::hasTable('media')) {
                $model->clearMediaCollection();
            }
            $model->delete();
        });
    }

    public function deleteMedia(User $user, PropertyAdvisement $model, Media $media): void
    {
        $this->authorizeOwner($user, $model);

        if (! Schema::hasTable('media') || $media->model_id !== $model->id || $media->model_type !== $model::class) {
            throw new AccessDeniedHttpException;
        }

        DB::transaction(function () use ($media): void {
            $media->delete();
        });
    }

    public function loadForShow(PropertyAdvisement $model): PropertyAdvisement
    {
        // Keep `.translation` — see create() comment.
        return $model->load([
            'propertyType.translation',
            'city.translation',
            'region.translation',
            'category.translation',
            'user',
            'media',
        ]);
    }

    private function authorizeOwner(User $user, PropertyAdvisement $model): void
    {
        if ($model->user_id !== $user->id || $model->user_type !== $user::class) {
            throw new AccessDeniedHttpException;
        }
    }
}
