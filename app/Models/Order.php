<?php

namespace App\Models;

use App\Enums\Order\OrderStatusEnum;
use App\Observers\OrderObserver;
use App\Traits\Reviewable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy(OrderObserver::class)]
class Order extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia,Reviewable;

    protected $attributes = [
        'status' => OrderStatusEnum::New, // Default status
    ];

    protected $keyType = 'string';

    protected $fillable = [
        'title', 'description', 'user_id', 'provider_id', 'category_id', 'price', 'status', 'expected_time', 'budget_start',
        'budget_end', 'accepted_offer_id', 'city_id', 'region_id',
        'user_fees', 'provider_fees', 'total_fees', 'user_total', 'provider_total',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(OrderOffer::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    public function acceptedOffer(): BelongsTo
    {
        return $this->belongsTo(OrderOffer::class, 'accepted_offer_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function orderSkills(): HasMany
    {
        return $this->hasMany(OrderSkill::class, 'order_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'order_skill', 'order_id', 'skill_id')
            ->using(OrderSkill::class);
    }

    public function conversation(): MorphOne
    {
        return $this->morphOne(Conversation::class, 'operation');
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatusEnum::class, // Enum for order status
        ];
    }

    protected function totalFees(): Attribute
    {
        return Attribute::get(static fn ($value, array $attributes) => $value ?? (($attributes['user_fees'] ?? 0) + ($attributes['provider_fees'] ?? 0)),
        );
    }

    protected function userTotal(): Attribute
    {
        return Attribute::get(static fn ($value, array $attributes) => $value ?? (($attributes['price'] ?? 0) + ($attributes['user_fees'] ?? 0)));
    }

    protected function providerTotal(): Attribute
    {
        return Attribute::get(static fn ($value, array $attributes) => $value ?? (($attributes['price'] ?? 0) - ($attributes['user_fees'] ?? 0)));
    }
}
