<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;

class WalletTransactionCollection extends BaseCollection
{
    public $collects = WalletTransactionResource::class;
}
