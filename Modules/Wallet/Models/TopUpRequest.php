<?php

namespace Modules\Wallet\Models;

use App\Enums\OperationStatusEnum;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use App\Models\Admin;
use Modules\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Wallet\Database\Factories\TopUpRequestFactory;

class TopUpRequest extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'user_type', 'amount', 'status', 'wallet_id', 'payment_method', 'transaction_id', 'payment_status',
        'admin_notes', 'transaction_image', 'user_notes', 'admin_id', 'payment_driver',
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

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'product');
    }

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethodEnum::class,
            'payment_status' => PaymentStatusEnum::class,
            'status' => OperationStatusEnum::class,
        ];
    }

    protected static function newFactory(): TopUpRequestFactory
    {
        return TopUpRequestFactory::new();
    }
}
