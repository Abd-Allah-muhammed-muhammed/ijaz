<?php

namespace Modules\Classifieds\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

class PropertyAdvisementService
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
        return DB::transaction(function () use ($user, $dto) {
            $propertyAdvisement = $this->repository->create([
                ...$dto->toPersistenceArray(),
                'user_type' => $user::class,
                'user_id' => $user->id,
                'status' => AdvisementStatusEnum::PENDING,
            ]);

            $this->storeAdvisementMediaAction->handle($propertyAdvisement, $dto->files);

            $propertyAdvisement->load([
                'propertyType.translation',
                'city.translation',
                'region.translation',
                'category.translation',
                'media',
            ]);

            return $propertyAdvisement;
        });
    }

    public function update(User $user, PropertyAdvisement $model, PropertyAdvisementDTO $dto): PropertyAdvisement
    {
        $this->authorizeOwner($user, $model);

        return DB::transaction(function () use ($model, $dto) {
            $propertyAdvisement = $this->repository->update($model, $dto->toPersistenceArray());

            $this->storeAdvisementMediaAction->handle($propertyAdvisement, $dto->files);

            $propertyAdvisement->load([
                'propertyType.translation',
                'city.translation',
                'region.translation',
                'category.translation',
                'media',
            ]);

            return $propertyAdvisement;
        });
    }

    public function delete(User $user, PropertyAdvisement $model): void
    {
        $this->authorizeOwner($user, $model);

        DB::transaction(function () use ($model) {
            $model->media->each->delete();
            $model->delete();
        });
    }

    public function deleteMedia(User $user, PropertyAdvisement $model, Media $media): void
    {
        $this->authorizeOwner($user, $model);

        if ($media->model()->isNot($model)) {
            throw new AccessDeniedHttpException('forbidden !!');
        }

        DB::transaction(function () use ($media) {
            $media->delete();
        });
    }

    public function loadForShow(PropertyAdvisement $model): PropertyAdvisement
    {
        $model->load([
            'propertyType.translation',
            'city.translation',
            'region.translation',
            'category.translation',
            'user',
            'media',
        ]);

        return $model;
    }

    private function authorizeOwner(User $user, PropertyAdvisement $model): void
    {
        if ($model->user_type !== $user::class || $model->user_id !== $user->id) {
            throw new AccessDeniedHttpException('forbidden !!');
        }
    }
}
