<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Orders\Enums\OrderStatusEnum;

class OrderStatusHistory extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id', 'status',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatusEnum::class,
        ];
    }
}
