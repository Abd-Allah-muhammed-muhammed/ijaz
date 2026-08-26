<?php

namespace Modules\Chat\Exceptions;

use App\Exceptions\ApiException;

class ChatMessageException extends ApiException
{
    public static function nullableMessage(): self
    {
        return new self("message can't be null");
    }
}
