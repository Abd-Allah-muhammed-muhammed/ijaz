<?php

namespace App\Support\Auth;

use App\Enums\ProviderTypeFilesEnum;
use App\Rules\MatchesDeclaredFileContent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Shared file-field validation for provider registration.
 *
 * Used by both the eager-upload endpoint and ProviderRegisterRequest (field
 * allow-list / size / mime expectations). Keep these rules identical — do not
 * diverge per-endpoint copies.
 */
final class ProviderRegistrationFileRules
{
    public const LOGO_FIELD = 'logo';

    /**
     * Laravel `max:` units are kilobytes.
     */
    public static function maxFileKilobytes(): int
    {
        return (int) config('provider_registration.max_file_kilobytes', 8192);
    }

    /**
     * @return list<string>
     */
    public static function allowedFields(): array
    {
        return [
            self::LOGO_FIELD,
            ...array_map(
                static fn (ProviderTypeFilesEnum $case): string => $case->value,
                ProviderTypeFilesEnum::cases(),
            ),
        ];
    }

    public static function isAllowedField(string $field): bool
    {
        return in_array($field, self::allowedFields(), true);
    }

    public static function isLogoField(string $field): bool
    {
        return $field === self::LOGO_FIELD;
    }

    /**
     * Validation rules for a raw UploadedFile on the eager-upload endpoint.
     *
     * @return array<int, ValidationRule|string>
     */
    public static function forUploadFile(string $field): array
    {
        $max = self::maxFileKilobytes();

        if (self::isLogoField($field)) {
            return [
                'required',
                'file',
                'image',
                "max:{$max}",
                new MatchesDeclaredFileContent(self::logoAllowedMimeTypes()),
            ];
        }

        return [
            'required',
            'file',
            'mimetypes:image/*,application/pdf',
            "max:{$max}",
            new MatchesDeclaredFileContent(self::certificateAllowedMimeTypes()),
        ];
    }

    /**
     * @return list<string>
     */
    public static function logoAllowedMimeTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];
    }

    /**
     * @return list<string>
     */
    public static function certificateAllowedMimeTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ];
    }

    /**
     * Field allow-list rule for the `field` request attribute.
     *
     * @return array<int, ValidationRule|string>
     */
    public static function fieldAttributeRules(): array
    {
        return ['required', 'string', Rule::in(self::allowedFields())];
    }
}
