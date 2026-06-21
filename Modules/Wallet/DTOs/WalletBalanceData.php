<?php

namespace Modules\Wallet\DTOs;

use Modules\Wallet\Models\Wallet;

final readonly class WalletBalanceData
{
    public function __construct(
        public float $balance,
        public float $pending_credit,
        public float $pending_debit,
        public float $available,
    ) {}

    public static function fromWallet(Wallet $wallet): self
    {
        return new self(
            balance: (float) $wallet->balance,
            pending_credit: (float) $wallet->pending_credit,
            pending_debit: (float) $wallet->pending_debit,
            available: (float) ($wallet->balance - $wallet->pending_debit),
        );
    }
}
