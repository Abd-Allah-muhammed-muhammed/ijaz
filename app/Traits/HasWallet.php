<?php

namespace App\Traits;

use App\Models\TopUpRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WithdrawRequest;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property Wallet $wallet
 * @property WalletTransaction[] $wallet_transactions
 * @property WithdrawRequest[] $withdraw_requests
 * @property float $balance
 */
trait HasWallet
{
    public static function bootHasWallet(): void
    {
        static::created(function ($model) {
            $model->wallet()->create([]);
        });
        static::deleted(function ($model) {
            $model->wallet()->delete();
        });

    }

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'user');
    }

    public function withdrawRequests(): MorphMany
    {
        return $this->morphMany(WithdrawRequest::class, 'user');
    }

    public function walletTTransactions(): MorphMany
    {
        return $this->morphMany(WalletTransaction::class, 'user');
    }

    public function topUpRequests(): MorphMany
    {
        return $this->morphMany(TopUpRequest::class, 'user');
    }

    protected function balance(): Attribute
    {
        return Attribute::get(fn () => $this->wallet->balance);
    }
}
