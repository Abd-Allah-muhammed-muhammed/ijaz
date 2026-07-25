<?php

namespace Modules\Chat\Exceptions;

use Exception;

class ChatException extends Exception
{
    public static function nullable(): ChatException
    {
        return new self("chat can't be null");
    }

    public static function notSupportChat(): ChatException
    {
        return new self('chat not support chat');
    }

    public static function chatDoesnotBelongToUser(?string $id = null): ChatException
    {
        return new self("chat doesn't belong to user {$id}");
    }

    public static function notAllowed(): ChatException
    {
        return new self('chat not allowed');
    }
}
