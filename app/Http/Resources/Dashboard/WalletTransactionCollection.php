<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Wallet\Models\WalletTransaction;

/** @see WalletTransaction
 * @mixin WalletTransaction
 */
class WalletTransactionCollection extends ResourceCollection
{
    public $collects = WalletTransactionResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
