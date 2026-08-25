<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Models\GuarantorRequest;

/**
 * Marks unpaid installments Voided when the parent guarantor reaches a
 * terminal outcome (Ended / Cancelled / Escalated / Settled).
 */
class VoidRemainingGuarantorInstallmentsAction
{
    public function __construct(
        private readonly InstallmentRepositoryInterface $installmentRepository,
    ) {}

    public function handle(GuarantorRequest $request): void
    {
        $this->installmentRepository->voidPendingOrOverdueForRequest($request);
    }
}
