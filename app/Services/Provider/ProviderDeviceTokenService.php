<?php

namespace App\Services\Provider;

use App\Actions\DeviceToken\RegisterDeviceTokenAction;
use App\Models\DeviceToken;
use App\Models\Provider;
use Illuminate\Http\Request;

class ProviderDeviceTokenService
{
    public const SESSION_WEB_FCM_TOKEN_KEY = 'provider_web_fcm_token';

    public function __construct(
        private readonly RegisterDeviceTokenAction $registerDeviceTokenAction,
    ) {}

    /**
     * Register an FCM web push token for the Provider dashboard session.
     * Stores the token in the session so logout can clear only this browser.
     */
    public function registerWebToken(Provider $provider, string $token, Request $request): DeviceToken
    {
        $deviceToken = $this->registerDeviceTokenAction->handle(
            $provider,
            $token,
            'web',
            'Provider Dashboard',
        );

        $request->session()->put(self::SESSION_WEB_FCM_TOKEN_KEY, trim($token));

        return $deviceToken;
    }
}
