<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Models\Wallet as WalletOwner;

class WalletTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'wallet_id', 'user_id', 'user_type', 'credit', 'debit', 'balance_before', 'balance_after', 'description',
        'operation_id', 'operation_type', 'pending_credit', 'pending_debit', 'payment_id', 'entry_kind',
    ];

    protected $keyType = 'string';

    public function operation(): MorphTo
    {
        return $this->morphTo();
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletOwner::class);
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'entry_kind' => WalletTransactionEntryKindEnum::class,
        ];
    }

    /**
     * Withdraw/top-up rows store a trans() key in description. Translate it for
     * the current locale; leave Orders/Guarantor/bonus (and hold-release) as stored.
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): string {
                $kind = $this->entry_kind;

                if (
                    ! $kind instanceof WalletTransactionEntryKindEnum
                    || $kind === WalletTransactionEntryKindEnum::WithdrawHoldReleased
                ) {
                    return (string) $value;
                }

                return trans((string) $value, [
                    'ref' => strtoupper(substr((string) $this->operation_id, -8)),
                ]);
            },
        );
    }
}
