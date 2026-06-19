<?php

namespace Modules\Chat\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\ChatTypeHandlerInterface;

class ListConversationsAction
{
    public function handle(
        Model $actor,
        ChatTypeHandlerInterface $handler,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $handler->listQuery($actor)->paginate($perPage);
    }
}
