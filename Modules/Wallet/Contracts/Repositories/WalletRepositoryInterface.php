<?php

namespace Modules\Wallet\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Models\Wallet;

interface WalletRepositoryInterface
{
    public function findOrCreate(Model $owner): Wallet;

    public function lockForUpdate(Model $owner): Wallet;

    /**
     * Atomically increase pending_debit only when available balance covers $amount.
     * Safe under concurrency even when SELECT FOR UPDATE is a no-op (e.g. SQLite).
     */
    public function tryIncrementPendingDebitIfAvailable(Wallet $wallet, float $amount): bool;
}
