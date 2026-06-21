<?php

namespace Modules\Wallet\Models;

use App\Enums\OperationStatusEnum;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Wallet\Database\Factories\WithdrawRequestFactory;

class WithdrawRequest extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'user_type', 'amount', 'status', 'wallet_id', 'admin_notes', 'user_notes', 'admin_id',
    ];

    protected $attributes = [
        'status' => OperationStatusEnum::Pending->value,
    ];

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    protected function casts(): array
    {
        return [
            'status' => OperationStatusEnum::class,
        ];
    }

    protected static function newFactory(): WithdrawRequestFactory
    {
        return WithdrawRequestFactory::new();
    }
}
