<?php

namespace App\Actions\Auth\Provider;

use App\Models\Provider;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class GenerateProviderAccountStatusGateUrlAction
{
    public const EXPIRY_MINUTES = 15;

    public function handle(Provider $provider): string
    {
        // Match SwitchLocaleAction: localize with the active request locale via
        // LaravelLocalization::getLocalizedURL(), then sign that final path so
        // localeSessionRedirect cannot mutate the URL out from under the signature.
        $locale = LaravelLocalization::getCurrentLocale() ?: app()->getLocale();

        $localizedUrl = LaravelLocalization::getLocalizedURL(
            $locale,
            route('provider.account-status', ['provider' => $provider->getKey()], absolute: true),
        );

        $localizedUrl = strtok((string) $localizedUrl, '?') ?: (string) $localizedUrl;
        $localizedUrl = rtrim($localizedUrl, '?');

        $expires = now()->addMinutes(self::EXPIRY_MINUTES)->getTimestamp();
        $payload = $localizedUrl.'?expires='.$expires;

        // Same key resolution as Illuminate\Routing\UrlGenerator's keyResolver.
        $keys = [config('app.key'), ...(config('app.previous_keys') ?? [])];
        $signature = hash_hmac('sha256', $payload, $keys[0]);

        return $payload.'&signature='.$signature;
    }
}
