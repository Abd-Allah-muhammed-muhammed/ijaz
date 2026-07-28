<?php

namespace Modules\Guarantor\Actions\Dashboard;

use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Models\GuarantorRequest;

class DeleteGuarantorForDashboardAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $repository,
    ) {}

    /**
     * Admin dashboard soft-delete — no status restriction.
     * Distinct from DeleteGuarantorAction (API PendingAdmin-only delete).
     */
    public function handle(GuarantorRequest $guarantorRequest): void
    {
        $this->repository->delete($guarantorRequest);
    }
}
