<?php

namespace Modules\Wallet\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Models\Wallet;

class WalletRepository implements WalletRepositoryInterface
{
    public function findOrCreate(Model $owner): Wallet
    {
        return $owner->wallet()->firstOrCreate([]);
    }

    public function lockForUpdate(Model $owner): Wallet
    {
        return $owner->wallet()->lockForUpdate()->firstOrCreate([]);
    }
}
