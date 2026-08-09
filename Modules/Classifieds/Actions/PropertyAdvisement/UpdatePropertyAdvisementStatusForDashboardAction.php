<?php

namespace Modules\Classifieds\Actions\PropertyAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\NotifyAdvisementOwnerOfStatusChangeAction;
use Modules\Classifieds\Contracts\Repositories\PropertyAdvisementRepositoryInterface;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\PropertyAdvisement;
use Throwable;

final class UpdatePropertyAdvisementStatusForDashboardAction
{
    public function __construct(
        private readonly PropertyAdvisementRepositoryInterface $repository,
        private readonly NotifyAdvisementOwnerOfStatusChangeAction $notifyOwnerAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PropertyAdvisement $advisement, AdvisementStatusEnum $status): PropertyAdvisement
    {
        return DB::transaction(function () use ($advisement, $status): PropertyAdvisement {
            $previous = $advisement->status;
            $advisement = $this->repository->update($advisement, ['status' => $status]);
            $this->notifyOwnerAction->handle($advisement, $previous, $status, 'property');

            return $advisement;
        });
    }
}
