<?php

namespace Modules\Classifieds\Actions\InstituteAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\DeleteAdvisementWithMediaAction;
use Modules\Classifieds\Models\InstituteAdvisement;
use Throwable;

final class DeleteInstituteAdvisementForDashboardAction
{
    public function __construct(
        private readonly DeleteAdvisementWithMediaAction $deleteAdvisementWithMediaAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(InstituteAdvisement $advisement): void
    {
        DB::transaction(function () use ($advisement): void {
            $this->deleteAdvisementWithMediaAction->handle($advisement);
        });
    }
}
