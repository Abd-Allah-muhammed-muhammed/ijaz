<?php

namespace App\Exceptions;

use App\Support\Api\ApiErrorResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

abstract class ApiException extends Exception
{
    /**
     * @param  array<string, mixed>|list<mixed>  $errors
     */
    public function __construct(
        string $message = '',
        int $code = 422,
        ?Throwable $previous = null,
        protected array $errors = [],
        protected bool $translateMessage = true,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->getCode() !== 0 ? (int) $this->getCode() : 422;
    }

    public function render(?Request $request = null): JsonResponse
    {
        $message = $this->translateMessage
            ? __($this->getMessage())
            : $this->getMessage();

        return ApiErrorResponse::failure(
            message: $message,
            statusCode: $this->getHttpStatusCode(),
            errors: $this->errors,
        );
    }
}
