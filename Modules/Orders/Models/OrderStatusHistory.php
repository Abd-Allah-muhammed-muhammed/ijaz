<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Orders\Enums\OrderStatusEnum;

class OrderStatusHistory extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'status',
        'from_status',
        'actor_id',
        'actor_type',
        'actor_name',
        'reason',
        'notes',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatusEnum::class,
        ];
    }

    protected function reason(): Attribute
    {
        return Attribute::make(
            get: fn (): ?array => $this->resolveReasonObject(),
            set: fn (?string $value): array => ['reason' => $value],
        );
    }

    /**
     * @return array{value: string, label: string}|null
     */
    private function resolveReasonObject(): ?array
    {
        $value = $this->rawReason();

        if ($value === null) {
            return null;
        }

        return [
            'value' => $value,
            'label' => $this->resolveReasonLabel($value),
        ];
    }

    private function rawReason(): ?string
    {
        $value = $this->attributes['reason'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    private function resolveReasonLabel(string $value): string
    {
        return match (true) {
            $value === 'dispute_resolved_full_user' => __('orders.dispute_outcome_full_user'),
            $value === 'dispute_resolved_full_provider' => __('orders.dispute_outcome_full_provider'),
            $value === 'dispute_escalated_to_court' => __('orders.dispute_outcome_escalated'),
            str_starts_with($value, 'dispute_resolved_percentage_split') => $this->percentageSplitReasonLabel($value),
            default => $value,
        };
    }

    private function percentageSplitReasonLabel(string $value): string
    {
        if (! str_contains($value, ':')) {
            return __('orders.dispute_outcome_percentage_split');
        }

        [, $ratio] = explode(':', $value, 2);
        [$user, $provider] = array_pad(explode('/', $ratio, 2), 2, null);

        if ($user === null || $provider === null || $user === '' || $provider === '') {
            return __('orders.dispute_outcome_percentage_split');
        }

        return __('orders.dispute_outcome_percentage_split_detail', [
            'user' => $user,
            'provider' => $provider,
        ]);
    }
}
