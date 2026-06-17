<?php

namespace Modules\Guarantor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Guarantor\Database\Factories\GuarantorInstallmentFactory;
use Modules\Guarantor\Enums\InstallmentStatusEnum;

class GuarantorInstallment extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'guarantor_request_id',
        'order',
        'amount',
        'due_date',
        'status',
        'paid_at',
        'released_at',
        'overdue_notified_at',
    ];

    protected $attributes = [
        'status' => InstallmentStatusEnum::Pending,
    ];

    public function guarantorRequest(): BelongsTo
    {
        return $this->belongsTo(GuarantorRequest::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', InstallmentStatusEnum::Pending);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', InstallmentStatusEnum::Pending)
            ->where('due_date', '<', now());
    }

    public function isPastDue(): bool
    {
        return $this->due_date->isPast()
            && $this->status->is(InstallmentStatusEnum::Pending);
    }

    protected static function newFactory(): GuarantorInstallmentFactory
    {
        return GuarantorInstallmentFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => InstallmentStatusEnum::class,
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'released_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
        ];
    }
}
