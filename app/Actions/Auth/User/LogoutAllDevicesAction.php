<?php

namespace App\Actions\Auth\User;

use App\Actions\DeviceToken\ClearAllDeviceTokensAction;
use App\Models\User;

class LogoutAllDevicesAction
{
    public function __construct(
        private readonly ClearAllDeviceTokensAction $clearAllDeviceTokensAction,
    ) {}

    public function handle(User $user): void
    {
        $user->tokens()->delete();
        $this->clearAllDeviceTokensAction->handle($user);
    }
}
