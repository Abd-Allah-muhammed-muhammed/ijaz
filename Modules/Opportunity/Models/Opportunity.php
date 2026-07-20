<?php

namespace Modules\Opportunity\Models;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Chat\Models\Conversation;
use Modules\Opportunity\Database\Factories\OpportunityFactory;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Opportunity extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'author_type',
        'author_id',
        'title',
        'description',
        'budget',
        'region_id',
        'city_id',
        'phone',
        'email',
        'status',
        'accepted_offer_id',
        'expires_at',
    ];

    protected $attributes = [
        'status' => OpportunityStatusEnum::New,
    ];

    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    public function offers(): HasMany
    {
        return $this->hasMany(OpportunityOffer::class);
    }

    public function acceptedOffer(): BelongsTo
    {
        return $this->belongsTo(OpportunityOffer::class, 'accepted_offer_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(OpportunityComment::class);
    }

    public function conversation(): MorphOne
    {
        return $this->morphOne(Conversation::class, 'operation');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('files')
            ->useDisk('public');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            OpportunityStatusEnum::Ended->value,
            OpportunityStatusEnum::Cancelled->value,
            OpportunityStatusEnum::Expired->value,
        ])->where(function (Builder $q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    #[Scope]
    protected function expired(Builder $query): Builder
    {
        return $query
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereIn('status', [
                OpportunityStatusEnum::New->value,
                OpportunityStatusEnum::OfferAccepted->value,
            ]);
    }

    #[Scope]
    protected function byActor(Builder $query, Model $actor): Builder
    {
        return $query
            ->where('author_type', $actor::class)
            ->where('author_id', $actor->getKey());
    }

    protected function casts(): array
    {
        return [
            'status' => OpportunityStatusEnum::class,
            'budget' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    protected static function newFactory(): OpportunityFactory
    {
        return OpportunityFactory::new();
    }
}
