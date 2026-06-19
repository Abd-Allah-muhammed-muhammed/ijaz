<?php

namespace Modules\Chat\Exceptions;

use Exception;

class ChatMessageException extends Exception
{
    public static function nullableMessage(): ChatMessageException
    {
        return new self("message can't be null");
    }
}
