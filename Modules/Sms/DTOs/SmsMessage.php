<?php

namespace Modules\Sms\DTOs;

use Carbon\Carbon;

/**
 * Outbound SMS payload for gateway::send() / sendMany().
 *
 * $body holds either an OTP code or a free-text message depending on the gateway.
 * $senderName and $scheduledAt are optional — used by Orbit, ignored by Authentica/Testing.
 */
final readonly class SmsMessage
{
    public function __construct(
        public string $body,
        public ?string $senderName = null,
        public ?Carbon $scheduledAt = null,
    ) {}

    /**
     * Shorthand for building an OTP-shaped message — used by existing OTP call sites.
     */
    public static function otp(string $code): self
    {
        return new self(body: $code);
    }

    public function isScheduled(): bool
    {
        return $this->scheduledAt !== null;
    }

    public function toArray(): array
    {
        return [
            'body' => $this->body,
            'sender_name' => $this->senderName,
            'scheduled_at' => $this->scheduledAt?->toIso8601String(),
        ];
    }
}
