<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Logger as MonologLogger;

/**
 * Tap that attaches sensitive-data redaction to single/daily (and similar) channels.
 */
final class ConfigureLogRedaction
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if ($monolog instanceof MonologLogger) {
            $monolog->pushProcessor(new RedactSensitiveDataProcessor);
        }
    }
}
