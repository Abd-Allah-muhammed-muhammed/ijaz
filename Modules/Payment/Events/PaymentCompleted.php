<?php

namespace Modules\Payment\Events;

use Modules\Payment\Models\Payment;

class PaymentCompleted
{
    public function __construct(
        public readonly Payment $payment,
    ) {}
}
