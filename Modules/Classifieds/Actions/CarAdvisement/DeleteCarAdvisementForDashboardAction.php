<?php

namespace Modules\Classifieds\Actions\CarAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\DeleteAdvisementWithMediaAction;
use Modules\Classifieds\Models\CarAdvisement;
use Throwable;

final class DeleteCarAdvisementForDashboardAction
{
    public function __construct(
        private readonly DeleteAdvisementWithMediaAction $deleteAdvisementWithMediaAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CarAdvisement $advisement): void
    {
        DB::transaction(function () use ($advisement): void {
            $this->deleteAdvisementWithMediaAction->handle($advisement);
        });
    }
}
