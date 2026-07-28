<?php

namespace App\Models;

use App\Enums\Auth\OtpPurposeEnum;
// Distinct alias required — see layered-architecture "Pint conflict" rule.
use App\Models\User as AuthUser;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpSession extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'purpose',
        'attempts_count',
        'max_attempts',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurposeEnum::class,
            'attempts_count' => 'integer',
            'max_attempts' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AuthUser::class);
    }

    public function isExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->expires_at);
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts_count >= $this->max_attempts;
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts_count');
    }
}
