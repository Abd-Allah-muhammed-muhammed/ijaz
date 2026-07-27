<?php

namespace Modules\Classifieds\Actions\ElectronicAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Contracts\Repositories\ElectronicAdvisementRepositoryInterface;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Throwable;

final class UpdateElectronicAdvisementStatusForDashboardAction
{
    public function __construct(
        private readonly ElectronicAdvisementRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(ElectronicAdvisement $advisement, AdvisementStatusEnum $status): ElectronicAdvisement
    {
        return DB::transaction(
            fn (): ElectronicAdvisement => $this->repository->update($advisement, ['status' => $status])
        );
    }
}
