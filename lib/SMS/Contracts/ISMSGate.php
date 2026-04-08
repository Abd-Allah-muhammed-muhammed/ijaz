<?php

namespace Lib\SMS\Contracts;

use Lib\SMS\DTOs\SMSMessage;
use Lib\SMS\DTOs\SMSResponse;

interface ISMSGate
{
    /**
     * send message to single number
     */
    public function send(SMSMessage $message, string $number): SMSResponse;

    /**
     * send message for many numbers
     */
    public function sendMany(SMSMessage $message, string ...$numbers): SMSResponse;
}
