<?php

namespace App\Actions\Auth\User;

use App\Actions\DeviceToken\ClearDeviceTokenAction;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutUserAction
{
    public function __construct(
        private readonly ClearDeviceTokenAction $clearDeviceTokenAction,
    ) {}

    public function handle(User $user): void
    {
        $accessToken = $user->currentAccessToken();

        if ($accessToken === null) {
            return;
        }

        if ($accessToken instanceof PersonalAccessToken) {
            $this->clearDeviceTokenAction->handle($user, (int) $accessToken->id);
            $accessToken->delete();

            return;
        }

        // TransientToken / non-persisted tokens: nothing to revoke in DB.
        if (method_exists($accessToken, 'delete')) {
            $accessToken->delete();
        }
    }
}
