<?php

use App\Models\User;
use App\Support\Api\ApiErrorResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use MMAE\ApiResponse\Configurations\Response as ApiResponseConfig;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Jobs\Exceptions\JobsException;
use Modules\Opportunity\Models\Opportunity;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Payout\Exceptions\PayoutException;
use Modules\Wallet\Exceptions\WalletException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @param  array<string, mixed>  $json
 */
function expectUnifiedApiErrorEnvelope(array $json, int $statusCode): void
{
    expect(array_keys($json))->toBe(['success', 'data', 'errors', 'message', 'token'])
        ->and($json['success'])->toBeFalse()
        ->and($json['data'])->toBe([])
        ->and($json['token'])->toBe('')
        ->and($json['errors'])->toBeArray()
        ->and($json['message'])->toBeString();
}

test('a GuarantorException thrown from any endpoint still returns the exact same envelope shape as before (success/data/errors/message/token) — regression', function () {
    $response = (new GuarantorException('guarantor.not_found', 404))->render();

    expect($response->getStatusCode())->toBe(404);
    expectUnifiedApiErrorEnvelope($response->getData(true), 404);

    expect($response->getData(true)['message'])->toBe(__('guarantor.not_found'));
});

test('an OrdersException thrown from any endpoint returns the unified envelope', function () {
    $response = (new OrdersException('you can not cancel this order', 422))->render();

    expect($response->getStatusCode())->toBe(422);
    expectUnifiedApiErrorEnvelope($response->getData(true), 422);
    expect($response->getData(true)['message'])->toBe(__('you can not cancel this order'));
});

test('a WalletException thrown from any endpoint returns the unified envelope (previously had no render() at all — was relying on controller catch-and-wrap)', function () {
    $response = (new WalletException('wallet.cannot_update_top_up_request_status', 422))->render();

    expect($response->getStatusCode())->toBe(422);
    expectUnifiedApiErrorEnvelope($response->getData(true), 422);
    expect($response->getData(true)['message'])->toBe(__('wallet.cannot_update_top_up_request_status'));
});

test('a PayoutException thrown from any endpoint returns the unified envelope', function () {
    $response = (new PayoutException('payout.cannot_fail_status', 422))->render();

    expect($response->getStatusCode())->toBe(422);
    expectUnifiedApiErrorEnvelope($response->getData(true), 422);
    expect($response->getData(true)['message'])->toBe(__('payout.cannot_fail_status'));
});

test('attempting to pay a Voided installment (GuarantorException via PayInstallmentAction) returns the unified envelope with a 422 status and the specific message, not raw Laravel debug output', function () {
    config(['app.debug' => true]);

    $requester = User::factory()->create();
    $counterparty = User::factory()->create();
    $guarantorRequest = GuarantorRequest::factory()->create([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'type' => GuarantorTypeEnum::Company,
        'status' => GuarantorStatusEnum::Accepted,
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Voided,
    ]);

    Sanctum::actingAs($counterparty);

    $response = $this->postJson(route('api.v1.guarantor.guarantor.installments.pay', [
        'guarantorRequest' => $guarantorRequest,
        'installment' => $installment,
    ]));

    $response->assertUnprocessable();
    expectUnifiedApiErrorEnvelope($response->json(), 422);
    expect($response->json('message'))->toBe(__('guarantor.pay_denied_installment_voided'))
        ->and($response->json())->not->toHaveKey('trace')
        ->and($response->json())->not->toHaveKey('file')
        ->and($response->json())->not->toHaveKey('exception');
});

test('the unified envelope never leaks a stack trace or file path field in the API response, regardless of APP_DEBUG', function () {
    config(['app.debug' => true]);

    $guarantorJson = (new GuarantorException('guarantor.not_found', 404))->render()->getData(true);
    $authJson = ApiErrorResponse::failure('Denied for testing', 403)->getData(true);
    $jobsJson = (new JobsException('job offer deleted successfully', 404))->render()->getData(true);

    foreach ([$guarantorJson, $authJson, $jobsJson] as $json) {
        expect($json)->not->toHaveKeys(['trace', 'file', 'line', 'exception']);
        expectUnifiedApiErrorEnvelope($json, 0);
    }

    $authorization = new AccessDeniedHttpException(__('guarantor.pay_denied_installment_voided'));
    $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
    $request = Request::create('/api/v1/test', 'POST', server: ['HTTP_ACCEPT' => 'application/json']);
    $rendered = $handler->render($request, $authorization);
    $authResponseJson = $rendered->getData(true);

    expect($authResponseJson)->not->toHaveKeys(['trace', 'file', 'line', 'exception']);
    expectUnifiedApiErrorEnvelope($authResponseJson, 403);
});

test('ModelNotFoundException and PostTooLargeException continue to render in the same envelope shape as before — regression, unaffected by this change', function () {
    $modelNotFound = ApiErrorResponse::failure(__('errors.not_found'), 404)->getData(true);
    expectUnifiedApiErrorEnvelope($modelNotFound, 404);

    $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
    $request = Request::create('/api/v1/opportunities/00000000-0000-0000-0000-000000000099', 'GET', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $response = $handler->render($request, (new ModelNotFoundException)->setModel(Opportunity::class));
    expectUnifiedApiErrorEnvelope($response->getData(true), 404);

    $postTooLargeResponse = $handler->render(
        Request::create('/up', 'POST', server: ['HTTP_ACCEPT' => 'application/json']),
        new PostTooLargeException('Too large'),
    );

    expect($postTooLargeResponse->getStatusCode())->toBe(ApiResponseConfig::$VALIDATION_FAILED_STATUS);
    expectUnifiedApiErrorEnvelope($postTooLargeResponse->getData(true), ApiResponseConfig::$VALIDATION_FAILED_STATUS);
    expect($postTooLargeResponse->getData(true))->toMatchArray([
        'success' => false,
        'data' => [],
        'errors' => [
            'files' => [__('One of your files exceeds the upload limit.')],
        ],
        'message' => ApiResponseConfig::$VALIDATION_FAILED_MESSAGE,
        'token' => '',
    ]);
});

test('AuthenticationException returns the unified envelope with 401 for JSON API requests', function () {
    config(['app.debug' => true]);

    $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
    $request = Request::create('/api/v1/user/auth/me', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
    $response = $handler->render($request, new \Illuminate\Auth\AuthenticationException('Unauthenticated.'));

    expect($response->getStatusCode())->toBe(401);
    expectUnifiedApiErrorEnvelope($response->getData(true), 401);
    expect($response->getData(true))->not->toHaveKeys(['trace', 'file', 'line', 'exception']);
});
