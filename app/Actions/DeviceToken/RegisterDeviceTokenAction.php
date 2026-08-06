<?php

namespace App\Actions\DeviceToken;

use App\Models\DeviceToken;
use Illuminate\Database\Eloquent\Model;

class RegisterDeviceTokenAction
{
    /**
     * Upsert an FCM token onto the tokenable. If another account already owns
     * the token (shared / re-registered device), ownership is reassigned here.
     *
     * When $personalAccessTokenId is provided, the row is linked to that Sanctum
     * session so logout can clear it without a mobile-supplied FCM string.
     *
     * Uses an atomic DB upsert on the unique `token` column so concurrent
     * registrations for the same device cannot create duplicates or throw.
     */
    public function handle(
        Model $tokenable,
        string $token,
        ?string $platform = null,
        ?string $deviceName = null,
        ?int $personalAccessTokenId = null,
    ): DeviceToken {
        $token = trim($token);
        $now = now();

        DeviceToken::query()->upsert(
            [
                [
                    'tokenable_type' => $tokenable->getMorphClass(),
                    'tokenable_id' => $tokenable->getKey(),
                    'personal_access_token_id' => $personalAccessTokenId,
                    'token' => $token,
                    'platform' => $platform,
                    'device_name' => $deviceName,
                    'last_used_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            uniqueBy: ['token'],
            update: [
                'tokenable_type',
                'tokenable_id',
                'personal_access_token_id',
                'platform',
                'device_name',
                'last_used_at',
                'updated_at',
            ],
        );

        return DeviceToken::query()->where('token', $token)->firstOrFail();
    }
}
