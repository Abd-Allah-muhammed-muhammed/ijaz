<?php

namespace Modules\Classifieds\Actions\InstituteAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Contracts\Repositories\InstituteAdvisementRepositoryInterface;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\InstituteAdvisement;
use Throwable;

final class UpdateInstituteAdvisementStatusForDashboardAction
{
    public function __construct(
        private readonly InstituteAdvisementRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(InstituteAdvisement $advisement, AdvisementStatusEnum $status): InstituteAdvisement
    {
        return DB::transaction(
            fn (): InstituteAdvisement => $this->repository->update($advisement, ['status' => $status])
        );
    }
}
