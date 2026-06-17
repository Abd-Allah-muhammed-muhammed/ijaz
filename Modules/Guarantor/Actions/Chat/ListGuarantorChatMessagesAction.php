<?php

namespace Modules\Guarantor\Actions\Chat;

use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListGuarantorChatMessagesAction
{
    public function handle(Conversation $conversation, int $perPage = 20): LengthAwarePaginator
    {
        return $conversation->messages()
            ->with(['sender', 'receiver', 'attachments'])
            ->latest()
            ->paginate($perPage);
    }
}
