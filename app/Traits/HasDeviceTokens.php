<?php

namespace App\Traits;

use App\Models\DeviceToken;
use App\Services\Firebase\DTO\Target;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasDeviceTokens
{
    public function deviceTokens(): MorphMany
    {
        return $this->morphMany(DeviceToken::class, 'tokenable');
    }

    /**
     * Upsert this FCM token onto the current model. If another account already owns
     * the token (shared / re-registered device), ownership is reassigned here.
     */
    public function registerDeviceToken(
        string $token,
        ?string $platform = null,
        ?string $deviceName = null,
    ): DeviceToken {
        $token = trim($token);

        DeviceToken::query()
            ->where('token', $token)
            ->where(function ($query): void {
                $query->where('tokenable_type', '!=', $this->getMorphClass())
                    ->orWhere('tokenable_id', '!=', $this->getKey());
            })
            ->delete();

        return $this->deviceTokens()->updateOrCreate(
            ['token' => $token],
            [
                'platform' => $platform,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ],
        );
    }

    public function clearDeviceToken(string $token): void
    {
        $this->deviceTokens()->where('token', trim($token))->delete();
    }

    public function clearAllDeviceTokens(): void
    {
        $this->deviceTokens()->delete();
    }

    /**
     * @return Collection<int, Target>
     */
    public function routeNotificationForFirebase(): Target|Collection
    {
        $tokens = $this->relationLoaded('deviceTokens')
            ? $this->deviceTokens
            : $this->deviceTokens()->get(['token']);

        return $tokens
            ->pluck('token')
            ->filter(fn (?string $token): bool => filled($token))
            ->values()
            ->map(fn (string $token): Target => Target::make('token', $token));
    }
}
