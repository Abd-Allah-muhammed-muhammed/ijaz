<?php

declare(strict_types=1);

namespace Lib\SMS\DTOs;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Data Transfer Object representing a SMS message.
 */
final readonly class SMSMessage implements Arrayable
{
    /**
     * @param  string  $text  Message text
     */
    public function __construct(protected string $text = '', protected string $otp = '') {}

    public function getText(): string
    {
        return $this->text;
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'otp' => $this->otp,
        ];
    }

    public function getOtp(): string
    {
        return $this->otp;
    }
}
