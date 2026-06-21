<?php

namespace Modules\Wallet\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Models\Wallet;

interface WalletRepositoryInterface
{
    public function findOrCreate(Model $owner): Wallet;

    public function lockForUpdate(Model $owner): Wallet;
}
