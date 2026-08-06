<?php

namespace App\Services\Firebase\DTO;

use InvalidArgumentException;

/**
 * Stateless FCM v1 message payload for a single FirebaseService::send() call.
 *
 * Built fresh per send by FirebaseChannel — never reused across notifications.
 */
final readonly class OutgoingFirebaseMessage
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $title,
        public string $body,
        public string $targetType,
        public string $targetValue,
        public array $data = [],
    ) {
        if (! in_array($this->targetType, ['token', 'topic'], true)) {
            throw new InvalidArgumentException("Invalid Firebase target type [{$this->targetType}]; supported: token, topic.");
        }

        if (trim($this->targetValue) === '') {
            throw new InvalidArgumentException('Firebase target value must not be empty.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function toToken(string $token, string $title, string $body, array $data = []): self
    {
        return new self($title, $body, 'token', $token, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function toTopic(string $topic, string $title, string $body, array $data = []): self
    {
        return new self($title, $body, 'topic', $topic, $data);
    }

    /**
     * FCM HTTP v1 "message" object (inner payload, without the outer wrapper).
     *
     * @return array<string, mixed>
     */
    public function toFcmMessage(): array
    {
        $message = [
            'notification' => [
                'title' => $this->title,
                'body' => $this->body,
            ],
            $this->targetType => $this->targetValue,
        ];

        if ($this->data !== []) {
            $message['data'] = $this->stringifyData($this->data);
        }

        return $message;
    }

    /**
     * FCM data values must be strings.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function stringifyData(array $data): array
    {
        $stringData = [];

        foreach ($data as $key => $value) {
            $stringData[(string) $key] = is_string($value)
                ? $value
                : (string) json_encode($value, JSON_THROW_ON_ERROR);
        }

        return $stringData;
    }
}
