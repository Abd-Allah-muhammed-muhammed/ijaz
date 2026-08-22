<?php

namespace Modules\Wallet\Exceptions;

use RuntimeException;

class MissingTransferStatusEagerLoadException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
