<?php

namespace Modules\Classifieds\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\AuthorizeAdvisementOwnerAction;
use Modules\Classifieds\Actions\DeleteAdvisementMediaAction;
use Modules\Classifieds\Actions\DeleteAdvisementWithMediaAction;
use Modules\Classifieds\Actions\PropertyAdvisement\ListPropertyAdvisementsForDashboardAction;
use Modules\Classifieds\Actions\PropertyAdvisement\ResolvePropertyAdvisementDashboardSelectsAction;
use Modules\Classifieds\Actions\PropertyAdvisement\UpdatePropertyAdvisementStatusForDashboardAction;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\PropertyAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\PropertyAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Classifieds\QueryFilters\PropertyAdvisementFilters;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class PropertyAdvisementService
{
    public function __construct(
        private readonly PropertyAdvisementRepositoryInterface $repository,
        private readonly ListPropertyAdvisementsForDashboardAction $listForDashboardAction,
        private readonly ResolvePropertyAdvisementDashboardSelectsAction $resolveDashboardSelectsAction,
        private readonly UpdatePropertyAdvisementStatusForDashboardAction $updateStatusForDashboardAction,
        private readonly StoreAdvisementMediaAction $storeAdvisementMediaAction,
        private readonly AuthorizeAdvisementOwnerAction $authorizeAdvisementOwnerAction,
        private readonly DeleteAdvisementWithMediaAction $deleteAdvisementWithMediaAction,
        private readonly DeleteAdvisementMediaAction $deleteAdvisementMediaAction,
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

    public function updateStatusForDashboard(PropertyAdvisement $advisement, AdvisementStatusEnum $status): PropertyAdvisement
    {
        return $this->updateStatusForDashboardAction->handle($advisement, $status);
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
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

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
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        DB::transaction(function () use ($model): void {
            $this->deleteAdvisementWithMediaAction->handle($model);
        });
    }

    public function deleteMedia(User $user, PropertyAdvisement $model, Media $media): void
    {
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        DB::transaction(function () use ($model, $media): void {
            $this->deleteAdvisementMediaAction->handle($model, $media);
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
}
