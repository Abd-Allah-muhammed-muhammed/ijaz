<?php

namespace Modules\Wallet\Support;

use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Models\WalletTransaction;

final class WalletTransactionDescription
{
    /**
     * Build the API/dashboard description. Withdraw/top-up rows with an
     * entry_kind are translated for the current locale; other rows keep the
     * stored column (Orders, Guarantor, bonus, legacy).
     */
    public static function for(mixed $transaction): string
    {
        if (! $transaction instanceof WalletTransaction) {
            return '';
        }

        $kind = $transaction->entry_kind;

        if (! $kind instanceof WalletTransactionEntryKindEnum) {
            return (string) $transaction->description;
        }

        if ($kind === WalletTransactionEntryKindEnum::WithdrawHoldReleased) {
            return (string) $transaction->description;
        }

        return trans('wallet.entry_kind.'.$kind->value, [
            'ref' => strtoupper(substr((string) $transaction->operation_id, -8)),
        ]);
    }
}
