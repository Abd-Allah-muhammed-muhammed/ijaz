<?php

namespace Modules\Cms\Actions\Message;

use Modules\Cms\Contracts\Repositories\MessageRepositoryInterface;
use Modules\Cms\Models\Message;

class DeleteMessageAction
{
    public function __construct(
        private readonly MessageRepositoryInterface $repository,
    ) {}

    public function handle(Message $message): void
    {
        $this->repository->delete($message);
    }
}
