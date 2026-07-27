<?php

namespace Modules\Classifieds\Actions\PropertyAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Contracts\Repositories\PropertyAdvisementRepositoryInterface;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\PropertyAdvisement;
use Throwable;

final class UpdatePropertyAdvisementStatusForDashboardAction
{
    public function __construct(
        private readonly PropertyAdvisementRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PropertyAdvisement $advisement, AdvisementStatusEnum $status): PropertyAdvisement
    {
        return DB::transaction(
            fn (): PropertyAdvisement => $this->repository->update($advisement, ['status' => $status])
        );
    }
}
