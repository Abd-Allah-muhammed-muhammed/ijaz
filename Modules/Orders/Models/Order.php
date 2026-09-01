<?php

namespace Modules\Orders\Models;

use App\Models\Provider;
use App\Models\User;
use App\Support\HasWebpImageConversion;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Chat\Models\Conversation;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\Skill;
use Modules\Orders\Database\Factories\OrderFactory;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Observers\OrderObserver;
use Modules\Reviews\Concerns\Reviewable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[ObservedBy(OrderObserver::class)]
class Order extends Model implements HasMedia
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUuids, HasWebpImageConversion, InteractsWithMedia, Reviewable;

    protected $attributes = [
        'status' => OrderStatusEnum::New,
    ];

    protected $keyType = 'string';

    protected $fillable = [
        'title', 'description', 'user_id', 'provider_id', 'category_id', 'price', 'status', 'expected_time', 'budget_start',
        'budget_end', 'accepted_offer_id', 'city_id', 'region_id',
        'user_fees', 'provider_fees', 'total_fees', 'user_total', 'provider_total',
        'wallet_settled_at', 'cancellation_reason', 'cancelled_at',
        'dispute_user_percentage', 'dispute_user_amount', 'dispute_provider_amount',
    ];

    /**
     * Percentage-split dispute resolution snapshot for API consumers.
     *
     * @return array{
     *     user_percentage: int,
     *     provider_percentage: int,
     *     user_amount: string,
     *     provider_amount: string
     * }|null
     */
    public function disputeResolutionForApi(): ?array
    {
        if ($this->dispute_user_percentage === null) {
            return null;
        }

        $userPercentage = (int) $this->dispute_user_percentage;

        return [
            'user_percentage' => $userPercentage,
            'provider_percentage' => 100 - $userPercentage,
            'user_amount' => number_format((float) $this->dispute_user_amount, 2, '.', ''),
            'provider_amount' => number_format((float) $this->dispute_provider_amount, 2, '.', ''),
        ];
    }

    protected static function newFactory(): Factory
    {
        return OrderFactory::new();
    }

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
            'status' => OrderStatusEnum::class,
            'wallet_settled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'dispute_user_amount' => 'decimal:2',
            'dispute_provider_amount' => 'decimal:2',
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
        return Attribute::get(static fn ($value, array $attributes) => $value ?? (($attributes['price'] ?? 0) - ($attributes['provider_fees'] ?? 0)));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerWebpImageConversion($media);
    }
}
