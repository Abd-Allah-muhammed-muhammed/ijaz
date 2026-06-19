<?php

namespace Modules\Chat\Exceptions;

use Exception;

class ChatUserException extends Exception
{
    public static function senderDoesNotExist(): ChatUserException
    {
        return new self('Sender does not exist');
    }

    public static function receiverDoesNotExist(): ChatUserException
    {
        return new self('Receiver does not exist');
    }

    public static function senderDoesNotBelongToChat(): ChatUserException
    {
        return new self('Sender does not belong to chat');

    }

    public static function userDoesNotBelongToChat(): ChatUserException
    {
        return new self('Receiver does not belong to chat');
    }
}
