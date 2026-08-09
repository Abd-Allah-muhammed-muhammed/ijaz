<?php

namespace Modules\Classifieds\Actions\ElectronicAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\NotifyAdvisementOwnerOfStatusChangeAction;
use Modules\Classifieds\Contracts\Repositories\ElectronicAdvisementRepositoryInterface;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Throwable;

final class UpdateElectronicAdvisementStatusForDashboardAction
{
    public function __construct(
        private readonly ElectronicAdvisementRepositoryInterface $repository,
        private readonly NotifyAdvisementOwnerOfStatusChangeAction $notifyOwnerAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(ElectronicAdvisement $advisement, AdvisementStatusEnum $status): ElectronicAdvisement
    {
        return DB::transaction(function () use ($advisement, $status): ElectronicAdvisement {
            $previous = $advisement->status;
            $advisement = $this->repository->update($advisement, ['status' => $status]);
            $this->notifyOwnerAction->handle($advisement, $previous, $status, 'electronic');

            return $advisement;
        });
    }
}
