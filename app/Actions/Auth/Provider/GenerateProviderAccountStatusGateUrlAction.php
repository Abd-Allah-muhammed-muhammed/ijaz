<?php

namespace App\Actions\Auth\Provider;

use App\Models\Provider;
use Illuminate\Support\Facades\URL;

class GenerateProviderAccountStatusGateUrlAction
{
    public const EXPIRY_MINUTES = 15;

    public function handle(Provider $provider): string
    {
        return URL::temporarySignedRoute(
            'provider.account-status',
            now()->addMinutes(self::EXPIRY_MINUTES),
            ['provider' => $provider->getKey()],
        );
    }
}
