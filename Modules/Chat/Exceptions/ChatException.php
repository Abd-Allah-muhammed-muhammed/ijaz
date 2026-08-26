<?php

namespace Modules\Chat\Exceptions;

use App\Exceptions\ApiException;

class ChatException extends ApiException
{
    public static function nullable(): self
    {
        return new self("chat can't be null");
    }

    public static function notSupportChat(): self
    {
        return new self('chat not support chat');
    }

    public static function chatDoesnotBelongToUser(?string $id = null): self
    {
        return new self("chat doesn't belong to user {$id}");
    }

    public static function notAllowed(): self
    {
        return new self('chat not allowed');
    }
}
