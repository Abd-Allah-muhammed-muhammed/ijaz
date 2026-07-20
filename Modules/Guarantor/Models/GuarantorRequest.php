<?php

namespace Modules\Guarantor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Chat\Models\Conversation;
use Modules\Guarantor\Database\Factories\GuarantorRequestFactory;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GuarantorRequest extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'type',
        'title',
        'description',
        'requester_id',
        'requester_type',
        'counterparty_id',
        'counterparty_type',
        'amount',
        'fees',
        'status',
        'project_type',
        'requester_signature',
        'cancellation_reason',
        'admin_notes',
        'overdue_at',
        'ended_at',
        'cancelled_at',
        'rejected_at',
        'refunded_at',
    ];

    protected $attributes = [
        'status' => GuarantorStatusEnum::PendingAdmin,
        'fees' => 10,
    ];

    public function requester(): MorphTo
    {
        return $this->morphTo();
    }

    public function counterparty(): MorphTo
    {
        return $this->morphTo();
    }

    public function installments(): HasMany
    {
        return $this->hasMany(GuarantorInstallment::class)
            ->orderBy('order');
    }

    public function companyDetail(): HasOne
    {
        return $this->hasOne(GuarantorCompanyDetail::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(GuarantorStatusHistory::class)
            ->latest();
    }

    public function conversation(): MorphOne
    {
        return $this->morphOne(Conversation::class, 'operation');
    }

    public function scopeForActor(Builder $query, Model $actor): Builder
    {
        return $query->where(function (Builder $q) use ($actor) {
            $q->where(function (Builder $q) use ($actor) {
                $q->where('requester_type', $actor::class)
                    ->where('requester_id', $actor->getKey());
            })->orWhere(function (Builder $q) use ($actor) {
                $q->where('counterparty_type', $actor::class)
                    ->where('counterparty_id', $actor->getKey());
            });
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            GuarantorStatusEnum::RejectedByAdmin->value,
            GuarantorStatusEnum::Rejected->value,
            GuarantorStatusEnum::Ended->value,
            GuarantorStatusEnum::Cancelled->value,
            GuarantorStatusEnum::Refunded->value,
        ]);
    }

    public function isCompany(): bool
    {
        return $this->type->is(GuarantorTypeEnum::Company);
    }

    public function isIndividual(): bool
    {
        return $this->type->is(GuarantorTypeEnum::Individual);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('signature')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('files')
            ->useDisk('public');
    }

    protected function total(): Attribute
    {
        return Attribute::get(static fn ($value, array $attributes) => $value ?? (($attributes['amount'] ?? 0) + ($attributes['fees'] ?? 0)));
    }

    protected static function newFactory(): GuarantorRequestFactory
    {
        return GuarantorRequestFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => GuarantorStatusEnum::class,
            'type' => GuarantorTypeEnum::class,
            'amount' => 'decimal:2',
            'fees' => 'decimal:2',
            'total' => 'decimal:2',
            'overdue_at' => 'datetime',
            'ended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'rejected_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }
}
