<?php

namespace Modules\Wallet\Http\Resources;

use App\Http\Resources\Api\BaseCollection;

class WalletTransactionCollection extends BaseCollection
{
    public $collects = WalletTransactionResource::class;
}
