<?php

namespace Modules\Wallet\Exceptions;

class InsufficientBalanceException extends WalletException
{
    public function __construct(float $available, float $requested)
    {
        parent::__construct(
            message: "Insufficient balance. Available: {$available}, Requested: {$requested}",
            code: 422,
        );
    }
}
