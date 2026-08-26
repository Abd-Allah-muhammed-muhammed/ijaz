<?php

namespace Modules\Wallet\Exceptions;

use App\Exceptions\ApiException;
use Throwable;

class WalletException extends ApiException
{
    public function __construct(
        string $message = 'Wallet operation failed',
        int $code = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
