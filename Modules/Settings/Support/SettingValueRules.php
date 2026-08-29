<?php

namespace Modules\Settings\Support;

/**
 * Pattern-based validation rules for settings keys by suffix.
 *
 * Unmatched keys stay unrestricted plain strings (free-text notes, URLs, etc.).
 */
final class SettingValueRules
{
    /**
     * @return list<string>|null Null means no suffix-specific rules.
     */
    public static function forKey(string $key): ?array
    {
        return match (true) {
            str_ends_with($key, '_percent') => ['nullable', 'numeric', 'min:0', 'max:100'],
            str_ends_with($key, '_fees'),
            str_ends_with($key, '_amount') => ['nullable', 'numeric', 'min:0'],
            str_ends_with($key, '_days'),
            str_ends_with($key, '_hours') => ['nullable', 'integer', 'min:0'],
            default => null,
        };
    }
}
