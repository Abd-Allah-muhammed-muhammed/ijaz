<?php

namespace App\Actions\Payment\Order;

use App\Models\Payment;
use Closure;

class NotifyUserForOrder
{
    public function __invoke(Payment $payment, Closure $next)
    {

        return $next($payment);
    }
}
