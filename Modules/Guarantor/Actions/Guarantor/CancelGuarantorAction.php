<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\ReverseGuarantorWalletHoldsAction as ReverseGuarantorWalletHolds;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorStatusAction as UpdateGuarantorStatus;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class CancelGuarantorAction
{
    public function __construct(
        private readonly UpdateGuarantorStatus $updateStatusAction,
        private readonly ReverseGuarantorWalletHolds $reverseGuarantorWalletHoldsAction,
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
            if ($request->status->isIn([
                GuarantorStatusEnum::Cancelled,
                GuarantorStatusEnum::Ended,
                GuarantorStatusEnum::Escalated,
                GuarantorStatusEnum::Settled,
            ])) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $this->updateStatusAction->handle(
                $request,
                new UpdateGuarantorStatusData(
                    status: GuarantorStatusEnum::Cancelled,
                    reason: $reason,
                    notes: $notes,
                ),
                $admin,
                'admin'
            );

            $this->reverseGuarantorWalletHoldsAction->handle($request->fresh());
        });
    }
}
