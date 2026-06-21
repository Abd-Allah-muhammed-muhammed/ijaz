<?php

namespace Modules\Payment\Events;

use Modules\Payment\Models\Payment;

class PaymentFailed
{
    public function __construct(
        public readonly Payment $payment,
    ) {}
}
