<?php

namespace App\Models;

use App\Enums\Order\OfferStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderOfferHistory extends Model
{
    use HasUuids;

    protected $table = 'order_offers_histories';

    protected $keyType = 'string';

    protected $fillable = [
        'order_id', 'order_offer_id', 'price', 'description', 'status',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderOffer(): BelongsTo
    {
        return $this->belongsTo(OrderOffer::class, 'order_offer_id');
    }

    protected function casts(): array
    {
        return [
            'status' => OfferStatusEnum::class,
        ];
    }
}
