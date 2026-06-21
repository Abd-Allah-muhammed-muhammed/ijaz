<?php

namespace Modules\Payment\DTOs;

use Modules\Payment\Enums\PaymentStatusEnum;

final readonly class PaymentVerifyResult
{
    public function __construct(
        public PaymentStatusEnum $status,
        public ?string $transactionId = null,
        public array $rawResponse = [],
        public ?string $message = null,
    ) {}

    public function isAccepted(): bool
    {
        return $this->status === PaymentStatusEnum::Accepted;
    }
}
