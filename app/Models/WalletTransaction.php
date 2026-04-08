<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'wallet_id', 'user_id', 'user_type', 'credit', 'debit', 'balance_before', 'balance_after', 'description',
        'operation_id', 'operation_type', 'pending_credit', 'pending_debit',
    ];

    protected $keyType = 'string';

    public function operation(): MorphTo
    {
        return $this->morphTo();
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
