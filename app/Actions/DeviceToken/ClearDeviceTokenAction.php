<?php

namespace App\Actions\DeviceToken;

use Illuminate\Database\Eloquent\Model;

class ClearDeviceTokenAction
{
    /**
     * Clear the device token linked to a Sanctum personal access token.
     * No-op when no row is linked (login without push registration).
     */
    public function handle(Model $tokenable, int $personalAccessTokenId): void
    {
        $tokenable->deviceTokens()
            ->where('personal_access_token_id', $personalAccessTokenId)
            ->delete();
    }
}
