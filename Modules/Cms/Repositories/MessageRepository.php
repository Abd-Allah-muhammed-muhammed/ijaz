<?php

namespace Modules\Cms\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Cms\Contracts\Repositories\MessageRepositoryInterface;
use Modules\Cms\Models\Message;

class MessageRepository implements MessageRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Message::query()
            ->when($request->input('search'), function (Builder $query, mixed $value) {
                return $query->where(function (Builder $q) use ($value) {
                    $q->where('name', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('title', 'like', "%{$value}%")
                        ->orWhere('content', 'like', "%{$value}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function create(array $data): Message
    {
        return Message::query()->create($data);
    }

    public function delete(Message $message): void
    {
        $message->delete();
    }
}
