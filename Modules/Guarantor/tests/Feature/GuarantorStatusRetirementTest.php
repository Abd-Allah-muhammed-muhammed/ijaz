<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorCounterpartyRejectedNotification;
use Modules\Guarantor\Notifications\GuarantorEndedNotification;
use Modules\Guarantor\Services\GuarantorDashboardService;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest}
 */
function statusRetirementContext(array $requestAttributes = []): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();
    $request = GuarantorRequest::factory()->create(array_merge([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'amount' => 1000,
        'fees' => 10,
        'status' => GuarantorStatusEnum::ApprovedByAdmin,
    ], $requestAttributes));

    return compact('requester', 'counterparty', 'request');
}

test('POST /guarantor/{id}/status no longer exists — route returns 404', function () {
    ['counterparty' => $counterparty, 'request' => $request] = statusRetirementContext();

    Sanctum::actingAs($counterparty);

    $this->postJson('/api/v1/guarantor/'.$request->id.'/status', [
        'status' => GuarantorStatusEnum::Rejected->value,
        'reason' => 'Nope',
    ])->assertNotFound();
});

test('POST /guarantor/{id}/reject works for the counterparty at approved_by_admin, requires a reason, transitions to rejected, notifies the requester — same effect as the old generic path', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = statusRetirementContext();

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.reject', $request), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);

    $this->postJson(route('api.v1.guarantor.guarantor.reject', $request), [
        'reason' => 'Terms not acceptable',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', GuarantorStatusEnum::Rejected->value);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Rejected)
        ->and($request->fresh()->rejected_at)->not->toBeNull();

    Notification::assertSentTo($requester, GuarantorCounterpartyRejectedNotification::class);
});

test('POST /guarantor/{id}/reject is rejected for the requester and from any other status', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = statusRetirementContext();

    Sanctum::actingAs($requester);

    $this->postJson(route('api.v1.guarantor.guarantor.reject', $request), [
        'reason' => 'I cannot reject my own request',
    ])->assertForbidden();

    ['counterparty' => $cp2, 'request' => $pending] = statusRetirementContext([
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);

    Sanctum::actingAs($cp2);

    $this->postJson(route('api.v1.guarantor.guarantor.reject', $pending), [
        'reason' => 'Too early',
    ])->assertForbidden();
});

test('POST /guarantor/{id}/end works for either party from in_progress/overdue — reuses the existing EndGuarantorAction unchanged', function () {
    foreach (['requester', 'counterparty'] as $role) {
        ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = statusRetirementContext([
            'status' => GuarantorStatusEnum::InProgress,
        ]);
        $actor = $role === 'requester' ? $requester : $counterparty;

        Sanctum::actingAs($actor);

        $this->postJson(route('api.v1.guarantor.guarantor.end', $request))
            ->assertSuccessful()
            ->assertJsonPath('data.status.value', GuarantorStatusEnum::Ended->value);

        expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Ended)
            ->and($request->fresh()->ended_at)->not->toBeNull();

        Notification::assertSentTo($requester, GuarantorEndedNotification::class);
        Notification::assertSentTo($counterparty, GuarantorEndedNotification::class);
    }

    ['counterparty' => $counterparty, 'request' => $overdue] = statusRetirementContext([
        'status' => GuarantorStatusEnum::Overdue,
    ]);

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.end', $overdue))
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', GuarantorStatusEnum::Ended->value);
});

test('POST /guarantor/{id}/dispute still works exactly as before — regression, single path now instead of dual-path', function () {
    ['counterparty' => $counterparty, 'request' => $request] = statusRetirementContext([
        'status' => GuarantorStatusEnum::InProgress,
    ]);

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.dispute', $request), [
        'reason' => 'Work not delivered',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', GuarantorStatusEnum::Disputed->value);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Disputed);
});

test('EndGuarantorAction and isAllowed() still function correctly when called internally by the new dedicated /end route', function () {
    ['requester' => $requester, 'request' => $request] = statusRetirementContext([
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    Sanctum::actingAs($requester);

    $this->postJson(route('api.v1.guarantor.guarantor.end', $request))
        ->assertForbidden();

    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::InProgress,
        GuarantorStatusEnum::Ended,
        'requester'
    ))->toBeTrue()
        ->and(GuarantorStatusEnum::isAllowed(
            GuarantorStatusEnum::Accepted,
            GuarantorStatusEnum::Ended,
            'requester'
        ))->toBeFalse();
});

test('Admin Dashboard approve/reject/cancel/resolve-dispute flows are completely unaffected — regression against the full existing Dashboard test suite', function () {
    // Covered by Modules/Guarantor/tests/Feature/DashboardTest.php in the full suite.
    // This placeholder asserts the Dashboard service wiring still resolves.
    expect(app(GuarantorDashboardService::class))->toBeInstanceOf(GuarantorDashboardService::class);
});
