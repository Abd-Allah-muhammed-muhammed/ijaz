<?php

namespace Modules\Support\Exceptions;

use App\Exceptions\ApiException;
use Throwable;

class TicketSupportNotDeletableException extends ApiException
{
    public function __construct(
        string $message = '',
        int $code = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous, translateMessage: false);
    }
}
