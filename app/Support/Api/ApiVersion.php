<?php

namespace App\Support\Api;

use InvalidArgumentException;

/**
 * Immutable view of one entry under config('api.versions').
 */
final readonly class ApiVersion
{
    public function __construct(
        public string $key,
        public bool $enabled,
        public string $folder,
        public string $prefix,
        public string $name,
        public bool $deprecated,
        public ?string $sunsetAt,
        public ?string $successor,
    ) {}

    public static function fromConfig(string $key): self
    {
        /** @var array<string, mixed>|null $config */
        $config = config("api.versions.{$key}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("API version [{$key}] is not defined in config/api.php.");
        }

        return new self(
            key: $key,
            enabled: (bool) ($config['enabled'] ?? false),
            folder: (string) ($config['folder'] ?? ''),
            prefix: (string) ($config['prefix'] ?? ''),
            name: (string) ($config['name'] ?? ''),
            deprecated: (bool) ($config['deprecated'] ?? false),
            sunsetAt: isset($config['sunset_at']) ? (string) $config['sunset_at'] : null,
            successor: isset($config['successor']) ? (string) $config['successor'] : null,
        );
    }

    /**
     * @return array{
     *     key: string,
     *     enabled: bool,
     *     folder: string,
     *     prefix: string,
     *     name: string,
     *     deprecated: bool,
     *     sunset_at: ?string,
     *     successor: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'enabled' => $this->enabled,
            'folder' => $this->folder,
            'prefix' => $this->prefix,
            'name' => $this->name,
            'deprecated' => $this->deprecated,
            'sunset_at' => $this->sunsetAt,
            'successor' => $this->successor,
        ];
    }
}
