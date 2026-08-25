<?php

namespace Modules\Guarantor\DTOs;

use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Models\GuarantorInstallment;

/**
 * Currently-held gross/fee/net for THIS guarantor (scoped, not wallet aggregates).
 */
final readonly class GuarantorHeldAmountData
{
    public function __construct(
        public float $gross,
        public float $fee,
        public float $net,
        public Model $operation,
        public ?GuarantorInstallment $installment = null,
    ) {}
}
