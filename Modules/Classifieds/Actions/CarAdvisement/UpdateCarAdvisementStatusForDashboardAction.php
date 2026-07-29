<?php

namespace Modules\Classifieds\Actions\CarAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Contracts\Repositories\CarAdvisementRepositoryInterface;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\CarAdvisement;
use Throwable;

final class UpdateCarAdvisementStatusForDashboardAction
{
    public function __construct(
        private readonly CarAdvisementRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CarAdvisement $advisement, AdvisementStatusEnum $status): CarAdvisement
    {
        return DB::transaction(
            fn (): CarAdvisement => $this->repository->update($advisement, ['status' => $status])
        );
    }
}
