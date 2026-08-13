<?php

namespace Modules\Wallet\Enums;

enum WalletTransactionLifecycleStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Rejected = 'rejected';
}
