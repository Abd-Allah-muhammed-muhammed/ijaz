<?php

namespace Modules\Opportunity\Actions\Chat;

use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class ListOpportunityChatMessagesAction
{
    public function handle(Conversation $conversation, Model $actor, int $perPage = 20): LengthAwarePaginator
    {
        $conversation->messages()
            ->whereNull('read_at')
            ->whereNotMorphedTo('sender', $actor)
            ->update(['read_at' => now()]);

        return $conversation->messages()
            ->with(['sender', 'receiver', 'attachments'])
            ->latest()
            ->paginate($perPage);
    }
}
