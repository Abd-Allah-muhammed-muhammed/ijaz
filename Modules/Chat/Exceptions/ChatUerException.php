<?php

namespace Modules\Chat\Exceptions;

use Exception;

class ChatUerException extends Exception
{
    public static function senderDoesNotExist(): ChatUerException
    {
        return new self('Sender does not exist');
    }

    public static function reseriverDoesNotExist(): ChatUerException
    {
        return new self('Reseriver does not exist');
    }

    public static function senderDoesNotBelongToChat(): ChatUerException
    {
        return new self('Sender does not belong to chat');

    }

    public static function userDoesNotBelongToChat(): ChatUerException
    {
        return new self('Reseriver does not belong to chat');
    }
}
