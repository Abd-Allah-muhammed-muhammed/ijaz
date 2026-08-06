<?php

namespace App\Actions\DeviceToken;

use Illuminate\Database\Eloquent\Model;

class ClearDeviceTokenAction
{
    public function handle(Model $tokenable, string $token): void
    {
        $tokenable->deviceTokens()->where('token', trim($token))->delete();
    }
}
