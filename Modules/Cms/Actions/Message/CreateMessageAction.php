<?php

namespace Modules\Cms\Actions\Message;

use Illuminate\Support\Facades\DB;
use Modules\Cms\Contracts\Repositories\MessageRepositoryInterface;
use Modules\Cms\DTOs\CreateMessageDTO;
use Modules\Cms\Models\Message;
use Throwable;

class CreateMessageAction
{
    public function __construct(
        private readonly MessageRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CreateMessageDTO $dto): Message
    {
        return DB::transaction(fn (): Message => $this->repository->create([
            'name' => $dto->name,
            'phone' => $dto->phone,
            'title' => $dto->title,
            'content' => $dto->content,
        ]));
    }
}
