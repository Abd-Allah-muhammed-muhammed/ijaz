<?php

namespace App\Models;

use App\Enums\Order\OfferStatusEnum;
use App\Observers\OrderOfferObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(OrderOfferObserver::class)]
class OrderOffer extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'user_id',
        'provider_id',
        'category_id',
        'price',
        'description',
        'status',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderOfferHistory::class, 'order_offer_id');
    }

    protected function casts(): array
    {
        return [
            'status' => OfferStatusEnum::class,
        ];
    }
}
