<?php

namespace App\Console\Commands\JsEnums\Attributes;

#[\Attribute]
class JsFunction
{
    private array $placeholders;

    public function __construct(public string $name, public array $arguments, public ?string $body = null, array $placeholders = [], public bool $ts = false)
    {
        $this->placeholders = [
            'APP_URL' => trim(url('/'), '/'),
            'ASSET_URL' => (string) trim(asset('/'), '/'),
            ...$placeholders,
        ];
    }

    public function getBody(): string
    {
        if ($this->body === null) {
            return '';
        }

        return str_replace(array_keys($this->placeholders), array_values($this->placeholders), $this->body);
    }
}
