<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class SMSLogger
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('sms');

        // Define the log file path
        $logPath = storage_path('logs/sms.log');

        // Create a StreamHandler
        $handler = new StreamHandler($logPath, Logger::INFO);

        // Optionally, set a custom formatter
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
        $handler->setFormatter($formatter);

        // Push the handler to the logger
        $logger->pushHandler($handler);

        return $logger;
    }
}
