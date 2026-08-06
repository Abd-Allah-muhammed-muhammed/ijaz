<?php

namespace App\Traits;

use App\Models\DeviceToken;
use App\Services\Firebase\DTO\FirebaseMessageTarget;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Data-access only. Token registration / clearing lives in
 * App\Actions\DeviceToken\* — callers must use those Actions.
 */
trait HasDeviceTokens
{
    public function deviceTokens(): MorphMany
    {
        return $this->morphMany(DeviceToken::class, 'tokenable');
    }

    /**
     * @return Collection<int, FirebaseMessageTarget>
     */
    public function routeNotificationForFirebase(): FirebaseMessageTarget|Collection
    {
        $tokens = $this->relationLoaded('deviceTokens')
            ? $this->deviceTokens
            : $this->deviceTokens()->get(['token']);

        return $tokens
            ->pluck('token')
            ->filter(fn (?string $token): bool => filled($token))
            ->values()
            ->map(fn (string $token): FirebaseMessageTarget => FirebaseMessageTarget::make('token', $token));
    }
}
