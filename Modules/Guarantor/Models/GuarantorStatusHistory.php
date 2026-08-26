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

    protected function reasonLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->resolveReasonLabel(),
        );
    }

    private function resolveReasonLabel(): ?string
    {
        if ($this->reason === null) {
            return null;
        }

        return match (true) {
            $this->reason === 'dispute_resolved_full_requester' => __('guarantor.dispute_outcome_full_requester'),
            $this->reason === 'dispute_resolved_full_counterparty' => __('guarantor.dispute_outcome_full_counterparty'),
            $this->reason === 'dispute_escalated_to_court' => __('guarantor.dispute_outcome_escalated'),
            $this->reason === 'dispute_closed_by_admin_cancel' => __('guarantor.dispute_outcome_admin_cancel'),
            str_starts_with($this->reason, 'dispute_resolved_percentage_split') => $this->percentageSplitReasonLabel(),
            default => $this->reason,
        };
    }

    private function percentageSplitReasonLabel(): string
    {
        if (! str_contains($this->reason, ':')) {
            return __('guarantor.dispute_outcome_percentage_split');
        }

        [, $ratio] = explode(':', $this->reason, 2);
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
