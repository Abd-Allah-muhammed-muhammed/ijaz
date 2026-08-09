<?php

namespace Modules\Classifieds\Actions\CarAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\NotifyAdvisementOwnerOfStatusChangeAction;
use Modules\Classifieds\Contracts\Repositories\CarAdvisementRepositoryInterface;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\CarAdvisement;
use Throwable;

final class UpdateCarAdvisementStatusForDashboardAction
{
    public function __construct(
        private readonly CarAdvisementRepositoryInterface $repository,
        private readonly NotifyAdvisementOwnerOfStatusChangeAction $notifyOwnerAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CarAdvisement $advisement, AdvisementStatusEnum $status): CarAdvisement
    {
        return DB::transaction(function () use ($advisement, $status): CarAdvisement {
            $previous = $advisement->status;
            $advisement = $this->repository->update($advisement, ['status' => $status]);
            $this->notifyOwnerAction->handle($advisement, $previous, $status, 'car');

            return $advisement;
        });
    }
}
