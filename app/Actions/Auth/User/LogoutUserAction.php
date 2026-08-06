<?php

namespace App\Actions\Auth\User;

use App\Actions\DeviceToken\ClearDeviceTokenAction;
use App\Models\User;

class LogoutUserAction
{
    public function __construct(
        private readonly ClearDeviceTokenAction $clearDeviceTokenAction,
    ) {}

    public function handle(User $user, ?string $deviceToken = null): void
    {
        $accessToken = $user->currentAccessToken();

        if ($accessToken !== null) {
            $accessToken->delete();
        }

        if (filled($deviceToken)) {
            $this->clearDeviceTokenAction->handle($user, $deviceToken);
        }
    }
}
