<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class SMSLogger
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('sms');

        $logPath = storage_path('logs/sms.log');

        $handler = new RotatingFileHandler($logPath, 14, Logger::INFO);

        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);

        return $logger;
    }
}
