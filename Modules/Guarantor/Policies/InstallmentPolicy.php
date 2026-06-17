<?php

namespace Modules\Guarantor\Policies;

use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Models\GuarantorInstallment;

class InstallmentPolicy
{
    public function pay(Model $user, GuarantorInstallment $installment): bool
    {
        $installment->loadMissing('guarantorRequest');
        $request = $installment->guarantorRequest;

        return $request->counterparty_type === $user::class
            && (string) $request->counterparty_id === (string) $user->getKey();
    }
}
