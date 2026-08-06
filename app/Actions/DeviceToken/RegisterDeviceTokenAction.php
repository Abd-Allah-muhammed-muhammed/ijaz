<?php

namespace App\Actions\DeviceToken;

use App\Models\DeviceToken;
use Illuminate\Database\Eloquent\Model;

class RegisterDeviceTokenAction
{
    /**
     * Upsert an FCM token onto the tokenable. If another account already owns
     * the token (shared / re-registered device), ownership is reassigned here.
     */
    public function handle(
        Model $tokenable,
        string $token,
        ?string $platform = null,
        ?string $deviceName = null,
    ): DeviceToken {
        $token = trim($token);

        DeviceToken::query()
            ->where('token', $token)
            ->where(function ($query) use ($tokenable): void {
                $query->where('tokenable_type', '!=', $tokenable->getMorphClass())
                    ->orWhere('tokenable_id', '!=', $tokenable->getKey());
            })
            ->delete();

        return $tokenable->deviceTokens()->updateOrCreate(
            ['token' => $token],
            [
                'platform' => $platform,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ],
        );
    }
}
