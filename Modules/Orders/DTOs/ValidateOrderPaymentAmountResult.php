<?php

namespace Modules\Orders\DTOs;

final readonly class ValidateOrderPaymentAmountResult
{
    public function __construct(
        public bool $isValid,
        public ?OrderFeesResult $fees,
        public float $expectedTotal,
        public float $paidAmount,
    ) {}
}
