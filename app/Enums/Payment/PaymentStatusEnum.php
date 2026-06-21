<?php

namespace App\Enums\Payment;

use Modules\Payment\Enums\PaymentStatusEnum;

/** @deprecated Use Modules\Payment\Enums\PaymentStatusEnum */
class_alias(PaymentStatusEnum::class, \App\Enums\Payment\PaymentStatusEnum::class);
