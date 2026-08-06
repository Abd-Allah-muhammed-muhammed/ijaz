<?php

namespace App\Services\Firebase\Exceptions;

use RuntimeException;
use Throwable;

class FirebaseAuthenticationException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
