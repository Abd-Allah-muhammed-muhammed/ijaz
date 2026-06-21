<?php

namespace Modules\Payment\Registry;

use Modules\Payment\Contracts\PaymentHandlerInterface;
use RuntimeException;

class PaymentHandlerRegistry
{
    private array $handlers = [];

    public function register(string $productType, PaymentHandlerInterface $handler): void
    {
        $this->handlers[$productType] = $handler;
    }

    public function getHandler(string $productType): PaymentHandlerInterface
    {
        if (! isset($this->handlers[$productType])) {
            throw new RuntimeException(
                "No payment handler registered for product type: {$productType}"
            );
        }

        return $this->handlers[$productType];
    }

    public function hasHandler(string $productType): bool
    {
        return isset($this->handlers[$productType]);
    }
}
