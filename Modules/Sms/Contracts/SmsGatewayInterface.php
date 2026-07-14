<?php

namespace Modules\Sms\Contracts;

use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\DTOs\SmsResult;

interface SmsGatewayInterface
{
    /**
     * Send a message to a single number.
     */
    public function send(SmsMessage $message, string $number): SmsResult;

    /**
     * Send a message to multiple numbers.
     */
    public function sendMany(SmsMessage $message, string ...$numbers): SmsResult;
}
