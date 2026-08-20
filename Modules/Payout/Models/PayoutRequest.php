<?php

namespace Modules\Payout\Models;

use App\Models\Admin;
use App\Support\HasWebpImageConversion;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Payout\Database\Factories\PayoutRequestFactory;
use Modules\Payout\Enums\PayoutStatusEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PayoutRequest extends Model implements HasMedia
{
    use HasFactory, HasUuids, HasWebpImageConversion, InteractsWithMedia;

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
        'maker_admin_id',
        'submitted_by_admin_id',
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

    public function makerAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'maker_admin_id');
    }

    public function submittedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'submitted_by_admin_id');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PayoutStatusEnum::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('transfer_proof')
            ->useDisk('public')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerWebpImageConversion($media);
    }

    /**
     * @return list<string>
     */
    protected function webpConversionCollections(): ?array
    {
        return ['transfer_proof'];
    }

    protected static function newFactory(): PayoutRequestFactory
    {
        return PayoutRequestFactory::new();
    }
}
