<?php

namespace Modules\Wallet\Support;

final class WalletTransactionDisplay
{
    public static function operationReference(?string $operationId): string
    {
        return strtoupper(substr((string) $operationId, -8));
    }

    public static function amount(
        float $credit,
        float $debit,
        float $pendingCredit,
        float $pendingDebit,
    ): float {
        return (float) max(
            abs($credit),
            abs($debit),
            abs($pendingCredit),
            abs($pendingDebit),
        );
    }

    public static function isPendingOnly(
        float $credit,
        float $debit,
        float $pendingCredit,
        float $pendingDebit,
    ): bool {
        if ($credit > 0 || $debit > 0) {
            return false;
        }

        return $pendingCredit > 0 || $pendingDebit > 0;
    }
}
