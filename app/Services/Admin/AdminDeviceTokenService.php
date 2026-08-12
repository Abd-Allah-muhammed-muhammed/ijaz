<?php

namespace App\Services\Admin;

use App\Actions\DeviceToken\RegisterDeviceTokenAction;
use App\Models\Admin;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class AdminDeviceTokenService
{
    public const SESSION_WEB_FCM_TOKEN_KEY = 'admin_web_fcm_token';

    public function __construct(
        private readonly RegisterDeviceTokenAction $registerDeviceTokenAction,
    ) {}

    /**
     * Register an FCM web push token for the Admin dashboard session.
     * Stores the token in the session so logout can clear only this browser.
     */
    public function registerWebToken(Admin $admin, string $token, Request $request): DeviceToken
    {
        $deviceToken = $this->registerDeviceTokenAction->handle(
            $admin,
            $token,
            'web',
            'Admin Dashboard',
        );

        $request->session()->put(self::SESSION_WEB_FCM_TOKEN_KEY, trim($token));

        return $deviceToken;
    }
}
