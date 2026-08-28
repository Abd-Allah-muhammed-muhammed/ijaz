<?php

namespace Modules\Classifieds\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\AuthorizeAdvisementOwnerAction;
use Modules\Classifieds\Actions\CarAdvisement\CreateCarAdvisementAction;
use Modules\Classifieds\Actions\CarAdvisement\DeleteCarAdvisementForDashboardAction;
use Modules\Classifieds\Actions\CarAdvisement\ListCarAdvisementsForDashboardAction;
use Modules\Classifieds\Actions\CarAdvisement\ResolveCarAdvisementDashboardSelectsAction;
use Modules\Classifieds\Actions\CarAdvisement\UpdateCarAdvisementAction;
use Modules\Classifieds\Actions\CarAdvisement\UpdateCarAdvisementStatusForDashboardAction;
use Modules\Classifieds\Actions\DeleteAdvisementMediaAction;
use Modules\Classifieds\Actions\DeleteAdvisementWithMediaAction;
use Modules\Classifieds\Contracts\Repositories\CarAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\CarAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\QueryFilters\CarAdvisementFilters;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class CarAdvisementService
{
    public function __construct(
        private readonly CarAdvisementRepositoryInterface $repository,
        private readonly ListCarAdvisementsForDashboardAction $listForDashboardAction,
        private readonly ResolveCarAdvisementDashboardSelectsAction $resolveDashboardSelectsAction,
        private readonly UpdateCarAdvisementStatusForDashboardAction $updateStatusForDashboardAction,
        private readonly DeleteCarAdvisementForDashboardAction $deleteForDashboardAction,
        private readonly CreateCarAdvisementAction $createCarAdvisementAction,
        private readonly UpdateCarAdvisementAction $updateCarAdvisementAction,
        private readonly AuthorizeAdvisementOwnerAction $authorizeAdvisementOwnerAction,
        private readonly DeleteAdvisementWithMediaAction $deleteAdvisementWithMediaAction,
        private readonly DeleteAdvisementMediaAction $deleteAdvisementMediaAction,
    ) {}

    public function listForDashboard(Request $request): LengthAwarePaginator
    {
        return $this->listForDashboardAction->handle($request);
    }

    /**
     * @return array{car_brand: array{value: int, label: string}|null, car_type: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null, category: array{value: int, label: string}|null}
     */
    public function resolveDashboardSelects(Request $request): array
    {
        return $this->resolveDashboardSelectsAction->handle($request);
    }

    public function updateStatusForDashboard(CarAdvisement $advisement, AdvisementStatusEnum $status): CarAdvisement
    {
        return $this->updateStatusForDashboardAction->handle($advisement, $status);
    }

    public function deleteForDashboard(CarAdvisement $advisement): void
    {
        $this->deleteForDashboardAction->handle($advisement);
    }

    public function listUserAdvisements(User $user, CarAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getUserAdvisements($user, $filters);
    }

    public function listPublishedAdvisements(CarAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getPublishedAdvisements($filters);
    }

    public function listPublishedAdvisementsForUser(User $user, CarAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getPublishedAdvisementsForUser($user, $filters);
    }

    public function create(User $user, CarAdvisementDTO $dto): CarAdvisement
    {
        return $this->createCarAdvisementAction->handle($user, $dto);
    }

    public function update(User $user, CarAdvisement $model, CarAdvisementDTO $dto): CarAdvisement
    {
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        return $this->updateCarAdvisementAction->handle($model, $dto);
    }

    public function delete(User $user, CarAdvisement $model): void
    {
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        DB::transaction(function () use ($model): void {
            $this->deleteAdvisementWithMediaAction->handle($model);
        });
    }

    public function deleteMedia(User $user, CarAdvisement $model, Media $media): void
    {
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        DB::transaction(function () use ($model, $media): void {
            $this->deleteAdvisementMediaAction->handle($model, $media);
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
}
