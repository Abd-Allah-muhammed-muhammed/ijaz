<?php

namespace App\Services\Firebase\DTO;

use Illuminate\Contracts\Support\Arrayable;

class Message implements Arrayable
{
    protected array $data = [
        'title' => '',
        'body' => '',
        'data' => [],
    ];

    public function __construct(string $title, string $body, array $data = [])
    {
        $this->data = [
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ];
    }

    public static function make(string $title, string $body, array $data = []): self
    {
        return new self($title, $body, $data);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function getTitle(): string
    {
        return $this->data['title'];
    }

    public function getBody(): string
    {
        return $this->data['body'];
    }

    public function getData(): array
    {
        return $this->data['data'];
    }

    public function isValid(): bool
    {
        return ! empty($this->data['title']) && ! empty($this->data['body']);
    }

    public function isNotValid(): bool
    {
        return ! $this->isValid();
    }
}
