<?php

namespace App\Actions\DeviceToken;

use Illuminate\Database\Eloquent\Model;

class ClearDeviceTokenByTokenAction
{
    /**
     * Clear a single device token string owned by the tokenable.
     * Used for session-scoped Provider web push logout (no Sanctum PAT).
     */
    public function handle(Model $tokenable, string $token): void
    {
        $token = trim($token);

        if ($token === '') {
            return;
        }

        $tokenable->deviceTokens()
            ->where('token', $token)
            ->delete();
    }
}
