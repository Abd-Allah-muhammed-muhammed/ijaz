<?php

namespace App\Actions\Auth\User;

use App\Models\User;

class LogoutAllDevicesAction
{
    public function handle(User $user): void
    {
        $user->tokens()->delete();
        $user->clearAllDeviceTokens();
    }
}
