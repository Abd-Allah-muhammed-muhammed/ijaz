<?php

namespace Modules\Classifieds\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\AuthorizeAdvisementOwnerAction;
use Modules\Classifieds\Actions\DeleteAdvisementMediaAction;
use Modules\Classifieds\Actions\DeleteAdvisementWithMediaAction;
use Modules\Classifieds\Actions\ElectronicAdvisement\CreateElectronicAdvisementAction;
use Modules\Classifieds\Actions\ElectronicAdvisement\DeleteElectronicAdvisementForDashboardAction;
use Modules\Classifieds\Actions\ElectronicAdvisement\ListElectronicAdvisementsForDashboardAction;
use Modules\Classifieds\Actions\ElectronicAdvisement\ResolveElectronicAdvisementDashboardSelectsAction;
use Modules\Classifieds\Actions\ElectronicAdvisement\UpdateElectronicAdvisementAction;
use Modules\Classifieds\Actions\ElectronicAdvisement\UpdateElectronicAdvisementStatusForDashboardAction;
use Modules\Classifieds\Contracts\Repositories\ElectronicAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\ElectronicAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\QueryFilters\ElectronicAdvisementFilters;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ElectronicAdvisementService
{
    public function __construct(
        private readonly ElectronicAdvisementRepositoryInterface $repository,
        private readonly ListElectronicAdvisementsForDashboardAction $listForDashboardAction,
        private readonly ResolveElectronicAdvisementDashboardSelectsAction $resolveDashboardSelectsAction,
        private readonly UpdateElectronicAdvisementStatusForDashboardAction $updateStatusForDashboardAction,
        private readonly DeleteElectronicAdvisementForDashboardAction $deleteForDashboardAction,
        private readonly CreateElectronicAdvisementAction $createElectronicAdvisementAction,
        private readonly UpdateElectronicAdvisementAction $updateElectronicAdvisementAction,
        private readonly AuthorizeAdvisementOwnerAction $authorizeAdvisementOwnerAction,
        private readonly DeleteAdvisementWithMediaAction $deleteAdvisementWithMediaAction,
        private readonly DeleteAdvisementMediaAction $deleteAdvisementMediaAction,
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

    public function updateStatusForDashboard(ElectronicAdvisement $advisement, AdvisementStatusEnum $status): ElectronicAdvisement
    {
        return $this->updateStatusForDashboardAction->handle($advisement, $status);
    }

    public function deleteForDashboard(ElectronicAdvisement $advisement): void
    {
        $this->deleteForDashboardAction->handle($advisement);
    }

    public function listUserAdvisements(User $user, ElectronicAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getUserAdvisements($user, $filters);
    }

    public function listPublishedAdvisements(ElectronicAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getPublishedAdvisements($filters);
    }

    public function listPublishedAdvisementsForUser(User $user, ElectronicAdvisementFilters $filters): LengthAwarePaginator
    {
        return $this->repository->getPublishedAdvisementsForUser($user, $filters);
    }

    public function create(User $user, ElectronicAdvisementDTO $dto): ElectronicAdvisement
    {
        return $this->createElectronicAdvisementAction->handle($user, $dto);
    }

    public function update(User $user, ElectronicAdvisement $model, ElectronicAdvisementDTO $dto): ElectronicAdvisement
    {
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        return $this->updateElectronicAdvisementAction->handle($model, $dto);
    }

    public function delete(User $user, ElectronicAdvisement $model): void
    {
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        DB::transaction(function () use ($model): void {
            $this->deleteAdvisementWithMediaAction->handle($model);
        });
    }

    public function deleteMedia(User $user, ElectronicAdvisement $model, Media $media): void
    {
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        DB::transaction(function () use ($model, $media): void {
            $this->deleteAdvisementMediaAction->handle($model, $media);
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
}
