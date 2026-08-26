<?php

namespace Modules\Guarantor\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Models\GuarantorInstallment;

class InstallmentPolicy
{
    public function release(Admin $admin, GuarantorInstallment $installment): Response
    {
        if (! $admin->can('manage guarantors')) {
            return Response::deny(__('guarantor.unauthorized'));
        }

        $installment->loadMissing('guarantorRequest');
        $request = $installment->guarantorRequest;

        if ($request->status->is(GuarantorStatusEnum::Disputed)) {
            return Response::deny(__('guarantor.release_denied_active_dispute'));
        }

        if ($request->status->isTerminal()) {
            return Response::deny(__('guarantor.release_denied_guarantor_terminal'));
        }

        if ($installment->status->is(InstallmentStatusEnum::Reversed)) {
            return Response::deny(__('guarantor.release_denied_installment_reversed'));
        }

        if ($installment->status->isNot(InstallmentStatusEnum::Paid)) {
            return Response::deny(__('guarantor.status_transition_not_allowed'));
        }

        return Response::allow();
    }

    public function pay(Model $user, GuarantorInstallment $installment): Response
    {
        $installment->loadMissing('guarantorRequest');
        $request = $installment->guarantorRequest;

        $isCounterparty = $request->counterparty_type === $user::class
            && (string) $request->counterparty_id === (string) $user->getKey();

        if (! $isCounterparty) {
            return Response::deny(__('guarantor.unauthorized'));
        }

        if ($installment->status->is(InstallmentStatusEnum::Voided)) {
            return Response::deny(__('guarantor.pay_denied_installment_voided'));
        }

        if ($installment->status->is(InstallmentStatusEnum::Reversed)) {
            return Response::deny(__('guarantor.release_denied_installment_reversed'));
        }

        if ($request->status->is(GuarantorStatusEnum::Disputed)) {
            return Response::deny(__('guarantor.pay_denied_active_dispute'));
        }

        if ($request->status->isTerminal()) {
            return Response::deny(__('guarantor.pay_denied_already_resolved'));
        }

        if ($request->status->isNotIn([
            GuarantorStatusEnum::Accepted,
            GuarantorStatusEnum::InProgress,
            GuarantorStatusEnum::Overdue,
        ])) {
            return Response::deny(__('guarantor.pay_denied_already_resolved'));
        }

        return Response::allow();
    }
}
