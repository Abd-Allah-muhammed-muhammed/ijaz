<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderRegistrationUploadCapExceededException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorKey = 'uploads',
    ) {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                $this->errorKey => [$this->getMessage()],
            ],
        ], 422);
    }
}
