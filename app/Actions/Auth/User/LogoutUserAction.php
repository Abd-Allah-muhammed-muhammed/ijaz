<?php

namespace App\Actions\Auth\User;

use App\Models\User;

class LogoutUserAction
{
    public function handle(User $user, ?string $deviceToken = null): void
    {
        $accessToken = $user->currentAccessToken();

        if ($accessToken !== null) {
            $accessToken->delete();
        }

        if (filled($deviceToken)) {
            $user->clearDeviceToken($deviceToken);
        }
    }
}
