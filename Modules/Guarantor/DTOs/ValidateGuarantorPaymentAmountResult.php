<?php

namespace Modules\Guarantor\DTOs;

final readonly class ValidateGuarantorPaymentAmountResult
{
    public function __construct(
        public bool $isValid,
        public float $expectedAmount,
        public float $paidAmount,
        public string $productLabel,
    ) {}
}
