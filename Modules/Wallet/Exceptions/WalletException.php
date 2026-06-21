<?php

namespace Modules\Wallet\Exceptions;

use Exception;
use Throwable;

class WalletException extends Exception
{
    public function __construct(
        string $message = 'Wallet operation failed',
        int $code = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
