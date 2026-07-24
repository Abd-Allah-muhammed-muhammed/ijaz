<?php

namespace Modules\Orders\DTOs;

final readonly class OrderFeesResult
{
    public function __construct(
        public float $price,
        public float $providerFees,
        public float $userFees,
    ) {}
}
