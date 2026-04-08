<?php

namespace App\Services\Firebase\DTO;

use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

class Target implements Arrayable
{
    public function __construct(protected string $type, protected ?string $value)
    {
        if (! in_array($this->type, ['topic', 'token'])) {
            throw new InvalidArgumentException("Invalid Argument type  '$this->type' in not supported ");
        }

    }

    public static function make(string $type, string $value): self
    {
        return new self($type, $value);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->value,
        ];
    }

    public function isNotValid(): bool
    {
        return ! $this->isValid();
    }

    public function isValid(): bool
    {
        return ! empty(trim($this->value)) && ! empty($this->type) && in_array($this->type, ['topic', 'token']);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
