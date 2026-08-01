<?php

namespace App\Support;

/**
 * Redacts sensitive values in log text before display (Log Viewer) or write (Monolog).
 */
final class LogRedactor
{
    /**
     * @param  list<array{pattern: string, replace: string}>  $patterns
     */
    public static function redact(string $content, ?array $patterns = null): string
    {
        $patterns ??= config('log-viewer.redact.patterns', []);

        if ($patterns === [] || ! config('log-viewer.redact.enabled', true)) {
            return $content;
        }

        foreach ($patterns as $rule) {
            $pattern = $rule['pattern'] ?? null;
            $replace = $rule['replace'] ?? '[REDACTED]';

            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            $redacted = preg_replace($pattern, $replace, $content);

            if (is_string($redacted)) {
                $content = $redacted;
            }
        }

        return $content;
    }
}
