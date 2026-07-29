<?php

namespace Modules\Classifieds\Actions\PropertyAdvisement;

use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\DeleteAdvisementWithMediaAction;
use Modules\Classifieds\Models\PropertyAdvisement;
use Throwable;

final class DeletePropertyAdvisementForDashboardAction
{
    public function __construct(
        private readonly DeleteAdvisementWithMediaAction $deleteAdvisementWithMediaAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PropertyAdvisement $advisement): void
    {
        DB::transaction(function () use ($advisement): void {
            $this->deleteAdvisementWithMediaAction->handle($advisement);
        });
    }
}
