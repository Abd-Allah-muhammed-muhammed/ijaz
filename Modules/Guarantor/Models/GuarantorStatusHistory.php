<?php

namespace Modules\Guarantor\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GuarantorStatusHistory extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'guarantor_request_id',
        'actor_id',
        'actor_type',
        'actor_name',
        'from_status',
        'to_status',
        'reason',
        'notes',
    ];

    public function guarantorRequest(): BelongsTo
    {
        return $this->belongsTo(GuarantorRequest::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
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
            $value === 'dispute_resolved_full_requester' => __('guarantor.dispute_outcome_full_requester'),
            $value === 'dispute_resolved_full_counterparty' => __('guarantor.dispute_outcome_full_counterparty'),
            $value === 'dispute_escalated_to_court' => __('guarantor.dispute_outcome_escalated'),
            $value === 'dispute_closed_by_admin_cancel' => __('guarantor.dispute_outcome_admin_cancel'),
            str_starts_with($value, 'dispute_resolved_percentage_split') => $this->percentageSplitReasonLabel($value),
            default => $value,
        };
    }

    private function percentageSplitReasonLabel(string $value): string
    {
        if (! str_contains($value, ':')) {
            return __('guarantor.dispute_outcome_percentage_split');
        }

        [, $ratio] = explode(':', $value, 2);
        [$requester, $counterparty] = array_pad(explode('/', $ratio, 2), 2, null);

        if ($requester === null || $counterparty === null || $requester === '' || $counterparty === '') {
            return __('guarantor.dispute_outcome_percentage_split');
        }

        return __('guarantor.dispute_outcome_percentage_split_detail', [
            'requester' => $requester,
            'counterparty' => $counterparty,
        ]);
    }
}
