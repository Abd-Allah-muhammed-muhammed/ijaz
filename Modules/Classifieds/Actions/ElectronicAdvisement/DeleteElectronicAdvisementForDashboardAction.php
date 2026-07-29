<?php

namespace Modules\Classifieds\Actions\ElectronicAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\DeleteAdvisementWithMediaAction;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Throwable;

final class DeleteElectronicAdvisementForDashboardAction
{
    public function __construct(
        private readonly DeleteAdvisementWithMediaAction $deleteAdvisementWithMediaAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(ElectronicAdvisement $advisement): void
    {
        DB::transaction(function () use ($advisement): void {
            $this->deleteAdvisementWithMediaAction->handle($advisement);
        });
    }
}
