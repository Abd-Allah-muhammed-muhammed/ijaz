<?php

namespace App\Enums\Chat;

use App\Enums\Utilities\HasOperations;

enum ChatEventEnum: string
{
    use HasOperations;
    case New_Message = 'new-message';
    case Chat_Updated = 'chat-updated';
}
