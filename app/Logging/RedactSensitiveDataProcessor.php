<?php

namespace App\Logging;

use App\Support\LogRedactor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Redacts sensitive strings in log messages and context before they are written to disk.
 */
final class RedactSensitiveDataProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $message = LogRedactor::redact($record->message);
        $context = $this->redactArray($record->context);
        $extra = $this->redactArray($record->extra);

        return $record->with(message: $message, context: $context, extra: $extra);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $data[$key] = '[REDACTED]';

                continue;
            }

            if (is_string($value)) {
                $data[$key] = LogRedactor::redact($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->redactArray($value);
            }
        }

        return $data;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);

        return str_contains($key, 'password')
            || str_contains($key, 'token')
            || str_contains($key, 'secret')
            || str_contains($key, 'authorization')
            || str_contains($key, 'cookie')
            || str_contains($key, 'api_key')
            || str_contains($key, 'apikey');
    }
}
