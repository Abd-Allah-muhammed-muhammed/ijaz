<?php

namespace App\Logging;

use App\Support\LogRedactor;
use Opcodes\LogViewer\Logs\LaravelLog;

/**
 * Laravel log parser for Log Viewer that masks sensitive values when rendering entries.
 *
 * opcodesio/log-viewer has no built-in redact config; this extends the laravel log type
 * so historical (and new) log files are masked in the UI.
 */
final class RedactingLaravelLog extends LaravelLog
{
    protected function parseText(array &$matches = []): void
    {
        parent::parseText($matches);

        $this->text = LogRedactor::redact($this->text);
        $this->message = LogRedactor::redact($this->message);

        if (is_array($this->context)) {
            array_walk_recursive($this->context, static function (mixed &$value): void {
                if (is_string($value)) {
                    $value = LogRedactor::redact($value);
                }
            });
        }
    }
}
