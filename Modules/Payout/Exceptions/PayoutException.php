<?php

namespace Modules\Payout\Exceptions;

use Exception;
use Throwable;

class PayoutException extends Exception
{
    public function __construct(
        string $message = 'payout.operation_failed',
        int $code = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
