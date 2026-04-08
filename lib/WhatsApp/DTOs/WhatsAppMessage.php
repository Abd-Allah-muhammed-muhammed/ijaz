<?php

declare(strict_types=1);

namespace Lib\WhatsApp\DTOs;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Data Transfer Object representing a WhatsApp message.
 */
final readonly class WhatsAppMessage implements Arrayable
{
    /**
     * @param  string  $text  Message text
     */
    public function __construct(
        protected string $text,
        protected array $attachments = [],
    ) {}

    public function getText(): string
    {
        return $this->text;
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'attachments' => $this->attachments,
        ];
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }
}
