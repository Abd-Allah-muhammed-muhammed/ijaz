<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegisterVerificationCode extends Model
{
    protected $fillable = [
        'token',
        'queryable',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->expires_at);
    }

    public function check(string $token): bool
    {
        return $token === $this->token && ! $this->isExpired();
    }
}
