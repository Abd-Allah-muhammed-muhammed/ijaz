<?php

namespace Modules\Guarantor\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

class InstallmentPolicy
{
    public function release(Admin $admin, GuarantorInstallment $installment, ?GuarantorRequest $guarantorRequest = null): Response
    {
        if (! $admin->can('manage guarantors')) {
            return Response::deny(__('guarantor.unauthorized'));
        }

        if ($guarantorRequest !== null
            && (string) $installment->guarantor_request_id !== (string) $guarantorRequest->getKey()) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function pay(Model $user, GuarantorInstallment $installment, ?GuarantorRequest $guarantorRequest = null): Response
    {
        if ($guarantorRequest !== null
            && (string) $installment->guarantor_request_id !== (string) $guarantorRequest->getKey()) {
            return Response::denyAsNotFound();
        }

        $installment->loadMissing('guarantorRequest');
        $request = $installment->guarantorRequest;

        $isCounterparty = $request->counterparty_type === $user::class
            && (string) $request->counterparty_id === (string) $user->getKey();

        if (! $isCounterparty) {
            return Response::deny(__('guarantor.unauthorized'));
        }

        return Response::allow();
    }
}
