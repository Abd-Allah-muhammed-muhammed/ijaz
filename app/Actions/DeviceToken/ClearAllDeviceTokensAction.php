<?php

namespace App\Actions\DeviceToken;

use Illuminate\Database\Eloquent\Model;

class ClearAllDeviceTokensAction
{
    public function handle(Model $tokenable): void
    {
        $tokenable->deviceTokens()->delete();
    }
}
