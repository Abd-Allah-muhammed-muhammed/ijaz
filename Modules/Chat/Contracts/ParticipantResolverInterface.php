<?php

namespace Modules\Chat\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ParticipantResolverInterface
{
    /**
     * Resolve a chat participant from a socket_id ("{type}-{id}").
     * Returns null for unknown types or when the participant does not exist.
     */
    public function resolveFromSocketId(string $socketId): ?Model;
}
