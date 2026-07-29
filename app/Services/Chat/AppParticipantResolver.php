<?php

namespace App\Services\Chat;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\ParticipantResolverInterface;

/**
 * App-owned participant lookup for Chat's socket_id format.
 * Keeps User/Provider Eloquent out of the Chat module.
 */
class AppParticipantResolver implements ParticipantResolverInterface
{
    public function resolveFromSocketId(string $socketId): ?Model
    {
        [$type, $id] = explode('-', $socketId, 2);

        return match ($type) {
            'user' => User::query()->find($id),
            'provider' => Provider::query()->find($id),
            default => null,
        };
    }
}
