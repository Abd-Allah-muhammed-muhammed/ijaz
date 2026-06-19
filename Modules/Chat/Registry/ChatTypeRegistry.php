<?php

namespace Modules\Chat\Registry;

use Modules\Chat\Contracts\ChatTypeHandlerInterface;
use Modules\Chat\Enums\ChatTypeEnum;
use RuntimeException;

class ChatTypeRegistry
{
    /** @var array<string, ChatTypeHandlerInterface> */
    private array $handlers = [];

    public function register(ChatTypeEnum $type, ChatTypeHandlerInterface $handler): void
    {
        $this->handlers[$type->value] = $handler;
    }

    public function get(ChatTypeEnum $type): ChatTypeHandlerInterface
    {
        if (! isset($this->handlers[$type->value])) {
            throw new RuntimeException("No handler registered for chat type: {$type->value}");
        }

        return $this->handlers[$type->value];
    }

    public function getByOperationType(string $operationType): ?ChatTypeHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->operationType() === $operationType) {
                return $handler;
            }
        }

        return null;
    }

    /** @return array<string, ChatTypeHandlerInterface> */
    public function all(): array
    {
        return $this->handlers;
    }
}
