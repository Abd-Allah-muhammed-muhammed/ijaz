<?php

namespace Modules\Wallet\DTOs;

use Illuminate\Support\Carbon;
use Modules\Wallet\Enums\WalletTransactionLifecycleStatus;

final readonly class WalletTransactionLifecycleData
{
    public function __construct(
        public string $operation_id,
        public string $operation_type,
        public WalletTransactionLifecycleStatus $status,
        public float $amount,
        public float $balance_before,
        public float $balance_after,
        public string $description,
        public ?Carbon $created_at,
    ) {}
}
