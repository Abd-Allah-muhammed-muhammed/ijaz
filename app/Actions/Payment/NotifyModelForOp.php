<?php

namespace App\Actions\Payment;

use App\Models\Payment;
use Closure;

class NotifyModelForOp
{
    public function __invoke(Payment $payment, Closure $next)
    {

        return $next($payment);
    }
}
