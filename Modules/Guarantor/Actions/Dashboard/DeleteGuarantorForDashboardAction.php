<?php

namespace Modules\Guarantor\Actions\Dashboard;

use Modules\Guarantor\Actions\Guarantor\DetermineGuarantorHeldAmountAction;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;

class DeleteGuarantorForDashboardAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $repository,
        private readonly DetermineGuarantorHeldAmountAction $determineGuarantorHeldAmountAction,
    ) {}

    /**
     * Admin dashboard soft-delete — blocked when an active dispute or unreleased holds exist.
     * Distinct from DeleteGuarantorAction (API PendingAdmin-only delete).
     */
    public function handle(GuarantorRequest $guarantorRequest): void
    {
        if ($guarantorRequest->status->is(GuarantorStatusEnum::Disputed)) {
            throw new GuarantorException('guarantor.delete_denied_active_dispute', 422);
        }

        if ($this->determineGuarantorHeldAmountAction->hasUnreleasedHold($guarantorRequest)) {
            throw new GuarantorException('guarantor.delete_denied_unreleased_holds', 422);
        }

        $this->repository->delete($guarantorRequest);
    }
}
