<?php

namespace Modules\Classifieds\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\AuthorizeAdvisementOwnerAction;
use Modules\Classifieds\Actions\DeleteAdvisementMediaAction;
use Modules\Classifieds\Actions\DeleteAdvisementWithMediaAction;
use Modules\Classifieds\Actions\PropertyAdvisement\CreatePropertyAdvisementAction;
use Modules\Classifieds\Actions\PropertyAdvisement\DeletePropertyAdvisementForDashboardAction;
use Modules\Classifieds\Actions\PropertyAdvisement\ListPropertyAdvisementsForDashboardAction;
use Modules\Classifieds\Actions\PropertyAdvisement\ResolvePropertyAdvisementDashboardSelectsAction;
use Modules\Classifieds\Actions\PropertyAdvisement\UpdatePropertyAdvisementAction;
use Modules\Classifieds\Actions\PropertyAdvisement\UpdatePropertyAdvisementStatusForDashboardAction;
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
        private readonly DeletePropertyAdvisementForDashboardAction $deleteForDashboardAction,
        private readonly CreatePropertyAdvisementAction $createPropertyAdvisementAction,
        private readonly UpdatePropertyAdvisementAction $updatePropertyAdvisementAction,
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

    public function deleteForDashboard(PropertyAdvisement $advisement): void
    {
        $this->deleteForDashboardAction->handle($advisement);
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
        return $this->createPropertyAdvisementAction->handle($user, $dto);
    }

    public function update(User $user, PropertyAdvisement $model, PropertyAdvisementDTO $dto): PropertyAdvisement
    {
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        return $this->updatePropertyAdvisementAction->handle($model, $dto);
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
        // Keep `.translation` — see CreatePropertyAdvisementAction comment.
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
