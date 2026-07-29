<?php

namespace Modules\Cms\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Cms\Actions\Message\CreateMessageAction;
use Modules\Cms\Actions\Message\DeleteMessageAction;
use Modules\Cms\Actions\Message\ListMessagesAction;
use Modules\Cms\DTOs\CreateMessageDTO;
use Modules\Cms\Models\Message;

class MessageService
{
    public function __construct(
        private readonly ListMessagesAction $listAction,
        private readonly CreateMessageAction $createAction,
        private readonly DeleteMessageAction $deleteAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function create(CreateMessageDTO $dto): Message
    {
        return $this->createAction->handle($dto);
    }

    public function destroy(Message $message): void
    {
        $this->deleteAction->handle($message);
    }
}
