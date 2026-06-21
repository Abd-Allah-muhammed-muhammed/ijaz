<?php

namespace Modules\Wallet\DTOs;

use Modules\Wallet\Enums\TransactionTypeEnum;

final readonly class WalletTransactionData
{
    public function __construct(
        public float $amount,
        public string $description,
        public string $operation_type,
        public string $operation_id,
        public TransactionTypeEnum $type,
        public float $credit = 0,
        public float $debit = 0,
        public float $pending_credit = 0,
        public float $pending_debit = 0,
        public float $balance_before = 0,
        public float $balance_after = 0,
        public ?string $payment_id = null,
    ) {}
}
