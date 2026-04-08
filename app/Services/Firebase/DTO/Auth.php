<?php

namespace App\Services\Firebase\DTO;

use Illuminate\Contracts\Support\Arrayable;

class Auth implements Arrayable
{
    public function __construct(private readonly string $project_id, private readonly string $path) {}

    public static function make(string $project_id, string $path): Auth
    {
        return new self($project_id, $path);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getProjectId(): string
    {
        return $this->project_id;
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->project_id,
            'path' => $this->path,
        ];
    }

    public function isValid(): bool
    {
        return $this->path && $this->project_id;
    }

    public function isNotValid(): bool
    {
        return ! $this->isValid();
    }

    public function fileExists(): bool
    {
        return file_exists($this->path) && is_file($this->path);
    }
}
