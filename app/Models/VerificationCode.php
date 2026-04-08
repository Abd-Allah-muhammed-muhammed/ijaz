<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VerificationCode extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $fillable = [
        'type',
        'token',
        'user_id',
        'user_type',
        'expire_at',
        'expiration_activated',
    ];

    protected $keyType = 'string';

    protected $casts = [
        'expire_at' => 'datetime',
        'expiration_activated' => 'bool',
    ];

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function isValid(): bool
    {
        return ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->expire_at);
    }

    public function verify(string $token, bool $check_expiration = true): bool
    {
        if ($check_expiration && $this->isExpired()) {
            return false;
        }
        try {
            return $this->token === $token;
        } catch (EncryptException $exception) {
            return false;
        }
    }

    //    protected function token(): Attribute
    //    {
    //        return Attribute::make(
    //            get: fn($value) => decrypt($value),
    //            set: fn($value) => encrypt($value),
    //        );
    //    }

}
