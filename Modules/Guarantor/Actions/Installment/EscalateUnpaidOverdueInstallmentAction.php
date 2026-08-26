<?php

namespace Modules\Guarantor\Actions\Installment;

use App\Contracts\Auth\AdminRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Notifications\UnpaidOverdueInstallmentEscalationNotification;

class EscalateUnpaidOverdueInstallmentAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
        private readonly InstallmentRepositoryInterface $installmentRepository,
    ) {}

    public function handle(GuarantorInstallment $installment): bool
    {
        $installment = $this->installmentRepository->refresh($installment);

        if ($installment->escalated_at !== null) {
            return false;
        }

        if ($installment->status->isNot(InstallmentStatusEnum::Pending)) {
            return false;
        }

        $admins = $this->adminRepository->getWithPermission('show guarantors');

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new UnpaidOverdueInstallmentEscalationNotification($installment));
        }

        Log::warning('Unpaid guarantor installment overdue past 14 days — admin escalation sent', [
            'guarantor_request_id' => $installment->guarantor_request_id,
            'installment_id' => $installment->id,
            'installment_order' => $installment->order,
            'amount' => $installment->amount,
            'due_date' => $installment->due_date->toDateString(),
        ]);

        $this->installmentRepository->update($installment, [
            'escalated_at' => now(),
        ]);

        return true;
    }
}
