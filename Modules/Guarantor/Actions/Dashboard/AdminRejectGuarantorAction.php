<?php

namespace Modules\Guarantor\Actions\Dashboard;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorStatusAction;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class AdminRejectGuarantorAction
{
    public function __construct(
        private readonly UpdateGuarantorStatusAction $updateStatusAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        GuarantorRequest $request,
        string $reason,
        ?string $notes,
        Admin $admin,
    ): void {
        DB::transaction(function () use ($request, $reason, $notes, $admin) {
            if ($request->status->isNot(GuarantorStatusEnum::PendingAdmin)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $this->updateStatusAction->handle(
                $request,
                new UpdateGuarantorStatusData(
                    status: GuarantorStatusEnum::RejectedByAdmin,
                    reason: $reason,
                    notes: $notes,
                ),
                $admin,
                'admin'
            );
        });
    }
}
