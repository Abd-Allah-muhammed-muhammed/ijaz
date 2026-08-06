<?php

use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Configurations\Response as ApiResponseConfig;

/**
 * Simulate PHP rejecting a body larger than post_max_size.
 *
 * ValidatePostSize compares CONTENT_LENGTH to ini_get('post_max_size').
 * We do not need a real multi-megabyte payload — only a CONTENT_LENGTH
 * larger than the configured limit — so this stays fast and deterministic.
 */
function contentLengthExceedingPostMaxSize(): int
{
    $postMaxSize = ini_get('post_max_size');

    if (is_numeric($postMaxSize)) {
        $maxBytes = (int) $postMaxSize;
    } else {
        $metric = strtoupper(substr((string) $postMaxSize, -1));
        $value = (int) $postMaxSize;

        $maxBytes = match ($metric) {
            'K' => $value * 1024,
            'M' => $value * 1048576,
            'G' => $value * 1073741824,
            default => $value,
        };
    }

    // post_max_size of 0 means unlimited — still exercise the handler via
    // CONTENT_LENGTH so CI environments with unlimited posts stay covered.
    return max($maxBytes, 1) + 1;
}

test('a request exceeding PHP post_max_size returns a graceful JSON error instead of a raw exception page', function () {
    $response = $this->withServerVariables([
        'CONTENT_LENGTH' => contentLengthExceedingPostMaxSize(),
    ])->postJson('/up');

    $response
        ->assertStatus(ApiResponseConfig::$VALIDATION_FAILED_STATUS)
        ->assertJson([
            'success' => false,
            'data' => [],
            'errors' => [
                'files' => [__('One of your files exceeds the upload limit.')],
            ],
            'message' => ApiResponseConfig::$VALIDATION_FAILED_MESSAGE,
            'token' => '',
        ]);
});

test('ValidatePostSize throws PostTooLargeException when CONTENT_LENGTH exceeds post_max_size', function () {
    $request = Request::create('/up', 'POST');
    $request->server->set('CONTENT_LENGTH', contentLengthExceedingPostMaxSize());

    expect(fn () => (new ValidatePostSize)->handle($request, fn () => response('ok')))
        ->toThrow(PostTooLargeException::class);
});
