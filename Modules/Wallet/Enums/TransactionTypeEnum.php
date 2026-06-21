<?php

namespace Modules\Wallet\Enums;

enum TransactionTypeEnum: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case PendingCredit = 'pending_credit';
    case PendingDebit = 'pending_debit';

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Credit',
            self::Debit => 'Debit',
            self::PendingCredit => 'Pending Credit',
            self::PendingDebit => 'Pending Debit',
        };
    }
}
