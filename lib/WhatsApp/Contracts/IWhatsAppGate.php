<?php

namespace Lib\WhatsApp\Contracts;

use Lib\WhatsApp\DTOs\WhatsAppMessage;
use Lib\WhatsApp\DTOs\WhatsAppResponse;

interface IWhatsAppGate
{
    /**
     * send message to single number
     */
    public function send(WhatsAppMessage $message, string $number): WhatsAppResponse;

    /**
     * send message for many numbers
     */
    public function sendMany(WhatsAppMessage $message, string ...$numbers): array;
}
