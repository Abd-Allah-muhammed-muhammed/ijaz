<?php

namespace Modules\Payout\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Payout\Database\Factories\PayoutRequestFactory;
use Modules\Payout\Enums\PayoutStatusEnum;

class PayoutRequest extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'operation_type',
        'operation_id',
        'recipient_type',
        'recipient_id',
        'amount',
        'status',
        'gateway_reference',
        'processed_by_admin_id',
        'failure_reason',
    ];

    protected $attributes = [
        'status' => PayoutStatusEnum::Pending->value,
    ];

    public function operation(): MorphTo
    {
        return $this->morphTo();
    }

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    public function processedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by_admin_id');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PayoutStatusEnum::class,
        ];
    }

    protected static function newFactory(): PayoutRequestFactory
    {
        return PayoutRequestFactory::new();
    }
}
