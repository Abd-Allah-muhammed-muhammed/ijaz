<?php

namespace Modules\Cms\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Cms\Models\Message;

interface MessageRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    public function create(array $data): Message;

    public function delete(Message $message): void;
}
