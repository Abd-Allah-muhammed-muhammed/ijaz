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
     * Uses an atomic DB upsert on the unique `token` column so concurrent
     * registrations for the same device cannot create duplicates or throw.
     */
    public function handle(
        Model $tokenable,
        string $token,
        ?string $platform = null,
        ?string $deviceName = null,
    ): DeviceToken {
        $token = trim($token);
        $now = now();

        DeviceToken::query()->upsert(
            [
                [
                    'tokenable_type' => $tokenable->getMorphClass(),
                    'tokenable_id' => $tokenable->getKey(),
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
                'platform',
                'device_name',
                'last_used_at',
                'updated_at',
            ],
        );

        return DeviceToken::query()->where('token', $token)->firstOrFail();
    }
}
