<?php

namespace Modules\Marketplace\Exceptions;

use App\Exceptions\ApiException;
use Throwable;

class MarketplaceException extends ApiException
{
    public function __construct(
        string $message = '',
        int $code = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous, translateMessage: false);
    }
}
