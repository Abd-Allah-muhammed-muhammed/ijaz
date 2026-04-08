<?php

namespace App\Actions\Payment\GuaranteeRequest;

use App\Models\Payment;
use Closure;

class NotifyUserForGuaranteeRequest
{
    public function __invoke(Payment $payment, Closure $next)
    {

        return $next($payment);
    }
}
