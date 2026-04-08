<?php

namespace Lib\SMS\Facade;

use Illuminate\Support\Facades\Facade;
use Lib\SMS\Contracts\ISMSGate;
use Lib\SMS\DTOs\SMSMessage;
use Lib\SMS\DTOs\SMSResponse;
use Lib\SMS\SMSManager;

/**
 * @method static ISMSGate driver(string $driver = null)
 * @method static SMSResponse send(SMSMessage $message,string $number)
 * @method static array sendMany(SMSMessage $message,string ...$numbers)
 *
 * @see SMSManager
 *
 * @mixin SMSManager
 */
class SMS extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SMSManager::class;
    }
}
