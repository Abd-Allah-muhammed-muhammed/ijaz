<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;

/**
 * Matches {@see HasApiResponse::makeResponse()} error envelope exactly.
 */
final class ApiErrorResponse
{
    /**
     * @param  array<string, mixed>|list<mixed>  $data
     * @param  array<string, mixed>|list<mixed>  $errors
     */
    public static function failure(
        string $message = '',
        int $statusCode = 422,
        array $data = [],
        array $errors = [],
        string $token = '',
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'data' => $data,
            'errors' => (object) $errors,
            'message' => $message,
            'token' => $token,
        ], $statusCode);
    }
}
