<?php

namespace App\Models;

use App\Enums\Auth\OtpPurposeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Otp extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'purpose',
        'subject_type',
        'subject_id',
        'phone',
        'token',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'purpose' => OtpPurposeEnum::class,
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->expires_at);
    }

    public function matches(string $token): bool
    {
        return ! $this->isExpired() && hash_equals((string) $this->token, $token);
    }
}
