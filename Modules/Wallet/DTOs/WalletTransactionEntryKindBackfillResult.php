<?php

namespace Modules\Wallet\DTOs;

final readonly class WalletTransactionEntryKindBackfillResult
{
    public function __construct(
        public int $withdrawRequested,
        public int $withdrawHoldReleased,
        public int $withdrawApproved,
        public int $withdrawRejected,
        public int $withdrawCancelled,
        public int $topupCredited,
        public int $total,
        public bool $dryRun,
    ) {}

    public function processed(): int
    {
        return $this->withdrawRequested
            + $this->withdrawHoldReleased
            + $this->withdrawApproved
            + $this->withdrawRejected
            + $this->withdrawCancelled
            + $this->topupCredited;
    }
}
