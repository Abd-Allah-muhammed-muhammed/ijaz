<?php

namespace Modules\Guarantor\Models;

use App\Support\HasWebpImageConversion;
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
use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Conversation;
use Modules\Guarantor\Database\Factories\GuarantorRequestFactory;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GuarantorRequest extends Model implements HasMedia
{
    use HasFactory, HasUuids, HasWebpImageConversion, InteractsWithMedia, SoftDeletes;

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
            GuarantorStatusEnum::Escalated->value,
            GuarantorStatusEnum::Settled->value,
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

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerWebpImageConversion($media);
    }

    /**
     * Images in `files` only — never convert signatures (legal document integrity).
     *
     * @return list<string>
     */
    protected function webpConversionCollections(): ?array
    {
        return ['files'];
    }

    protected static function booted(): void
    {
        // MySQL uses a STORED GENERATED total column. SQLite uses a plain column
        // defaulting to 0 — keep it in sync on write so Dashboard/API totals stay correct.
        static::saving(function (GuarantorRequest $model): void {
            if (DB::getDriverName() !== 'sqlite') {
                return;
            }

            $model->attributes['total'] = number_format(
                (float) ($model->attributes['amount'] ?? $model->amount ?? 0)
                + (float) ($model->attributes['fees'] ?? $model->fees ?? 0),
                2,
                '.',
                ''
            );
        });
    }

    protected function total(): Attribute
    {
        // Prefer derived amount+fees when the stored total is missing or a stale
        // SQLite default of 0 while the contract principal implies a real total.
        return Attribute::get(static function ($value, array $attributes): string {
            $derived = (float) ($attributes['amount'] ?? 0) + (float) ($attributes['fees'] ?? 0);
            $stored = $attributes['total'] ?? null;

            if ($stored === null || ((float) $stored === 0.0 && $derived > 0.0)) {
                return number_format($derived, 2, '.', '');
            }

            return number_format((float) $stored, 2, '.', '');
        });
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
            'overdue_at' => 'datetime',
            'ended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'rejected_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }
}
