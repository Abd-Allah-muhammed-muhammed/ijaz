<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'user_type', 'balance', 'pending_credit', 'pending_debit', 'total_earning', 'total_spent', 'debit', 'credit',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
