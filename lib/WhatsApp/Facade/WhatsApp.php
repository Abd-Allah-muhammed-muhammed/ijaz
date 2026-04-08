<?php

namespace Lib\WhatsApp\Facade;

use Illuminate\Support\Facades\Facade;
use Lib\WhatsApp\Contracts\IWhatsAppGate;
use Lib\WhatsApp\DTOs\WhatsAppMessage;
use Lib\WhatsApp\DTOs\WhatsAppResponse;
use Lib\WhatsApp\WhatsAppManager;

/**
 * @method static IWhatsAppGate driver(string $driver = null)
 * @method static WhatsAppResponse send(WhatsAppMessage $message,string $number)
 * @method static array sendMany(WhatsAppMessage $message,string ...$numbers)
 *
 * @see WhatsAppManager
 *
 * @mixin WhatsAppManager
 */
class WhatsApp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WhatsAppManager::class;
    }
}
