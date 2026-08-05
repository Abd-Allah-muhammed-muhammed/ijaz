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

    public function tryIncrementPendingDebitIfAvailable(Wallet $wallet, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        // Use `(available) - ? >= 0` rather than `available >= ?`: SQLite binds PHP
        // floats as a type that fails numeric column comparisons with `>= ?`.
        $affected = Wallet::query()
            ->whereKey($wallet->getKey())
            ->whereRaw('(balance - pending_debit) - ? >= 0', [$amount])
            ->increment('pending_debit', $amount);

        if ($affected > 0) {
            $wallet->refresh();
        }

        return $affected > 0;
    }
}
