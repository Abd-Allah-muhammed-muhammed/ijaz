<?php

namespace Modules\Classifieds\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\AuthorizeAdvisementOwnerAction;
use Modules\Classifieds\Actions\DeleteAdvisementMediaAction;
use Modules\Classifieds\Actions\DeleteAdvisementWithMediaAction;
use Modules\Classifieds\Actions\InstituteAdvisement\ListInstituteAdvisementsForDashboardAction;
use Modules\Classifieds\Actions\InstituteAdvisement\ResolveInstituteAdvisementDashboardSelectsAction;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\InstituteAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\InstituteAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\QueryFilters\InstituteAdvisementFilters;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class InstituteAdvisementService
{
    public function __construct(
        private readonly InstituteAdvisementRepositoryInterface $repository,
        private readonly ListInstituteAdvisementsForDashboardAction $listForDashboardAction,
        private readonly ResolveInstituteAdvisementDashboardSelectsAction $resolveDashboardSelectsAction,
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
     * @return array{status: array{value: string, label: string, color: string}|null, type: array{value: string, label: string, color: string}|null, study_type: array{value: string, label: string, color: string}|null, study_level: array{value: string, label: string, color: string}|null, specialization: array{value: int, label: string}|null, city: array{value: int, label: string}|null, region: array{value: int, label: string}|null}
     */
    public function resolveDashboardSelects(Request $request): array
    {
        return $this->resolveDashboardSelectsAction->handle($request);
    }

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
            $instituteAdvisement = $this->repository->create([
                ...$dto->toPersistenceArray(),
                'user_type' => $user::class,
                'user_id' => $user->id,
                'status' => AdvisementStatusEnum::PENDING,
            ]);

            $this->storeAdvisementMediaAction->handle($instituteAdvisement, $dto->files);
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
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        return DB::transaction(function () use ($model, $dto): InstituteAdvisement {
            $this->repository->update($model, $dto->toPersistenceArray());
            $this->storeAdvisementMediaAction->handle($model, $dto->files);
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
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        DB::transaction(function () use ($model): void {
            $this->deleteAdvisementWithMediaAction->handle($model);
        });
    }

    public function deleteMedia(User $user, InstituteAdvisement $model, Media $media): void
    {
        $this->authorizeAdvisementOwnerAction->handle($model, $user);

        DB::transaction(function () use ($model, $media): void {
            $this->deleteAdvisementMediaAction->handle($model, $media);
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
}
