<?php

namespace Modules\Classifieds\Actions\InstituteAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\NotifyAdvisementOwnerOfStatusChangeAction;
use Modules\Classifieds\Contracts\Repositories\InstituteAdvisementRepositoryInterface;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\InstituteAdvisement;
use Throwable;

final class UpdateInstituteAdvisementStatusForDashboardAction
{
    public function __construct(
        private readonly InstituteAdvisementRepositoryInterface $repository,
        private readonly NotifyAdvisementOwnerOfStatusChangeAction $notifyOwnerAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(InstituteAdvisement $advisement, AdvisementStatusEnum $status): InstituteAdvisement
    {
        return DB::transaction(function () use ($advisement, $status): InstituteAdvisement {
            $previous = $advisement->status;
            $advisement = $this->repository->update($advisement, ['status' => $status]);
            $this->notifyOwnerAction->handle($advisement, $previous, $status, 'institute');

            return $advisement;
        });
    }
}
