<?php

namespace Modules\Jobs\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class JobsException extends Exception
{
    public function __construct(
        private readonly string $translationKey,
        private readonly int $httpStatusCode = 404,
    ) {
        parent::__construct($translationKey);
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => __($this->translationKey),
            'data' => [],
            'errors' => [],
        ], $this->httpStatusCode);
    }
}
