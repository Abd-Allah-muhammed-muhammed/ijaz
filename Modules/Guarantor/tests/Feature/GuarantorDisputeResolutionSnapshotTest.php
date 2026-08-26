<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Guarantor\Actions\Guarantor\OpenGuarantorDisputeAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputePercentageSplitAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Http\Resources\Api\GuarantorResource;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorDisputeResolvedNotification;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest, admin: Admin}
 */
function disputeResolutionSnapshotContext(array $requestAttributes = []): array
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
        'status' => GuarantorStatusEnum::Accepted,
    ], $requestAttributes));

    Permission::firstOrCreate(['name' => 'manage guarantors', 'guard_name' => 'admin']);
    $admin = Admin::query()->create([
        'name' => 'Resolution Snapshot Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo('manage guarantors');

    return compact('requester', 'counterparty', 'request', 'admin');
}

function completeDisputeResolutionSnapshotPayment($owner, $product, float $amount): void
{
    $payment = createPaymentFor($owner, $product, [
        'amount' => $amount,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment->load('product')));
}

function openDisputeForResolutionSnapshot(GuarantorRequest $request, User $actor): GuarantorRequest
{
    return app(OpenGuarantorDisputeAction::class)->handle(
        $request->fresh(),
        $actor,
        'requester',
        'Dispute for resolution snapshot',
    );
}

test('resolving a dispute via percentage split persists requester_percentage, counterparty_percentage, requester_amount, and counterparty_amount on the GuarantorRequest', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeResolutionSnapshotContext();
    completeDisputeResolutionSnapshotPayment($counterparty, $request, 1010);
    openDisputeForResolutionSnapshot($request, $requester);

    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60);

    $settled = $request->fresh();

    expect($settled->dispute_requester_percentage)->toBe(60)
        ->and($settled->dispute_requester_amount)->toBe('600.00')
        ->and($settled->dispute_counterparty_amount)->toBe('404.00')
        ->and(100 - $settled->dispute_requester_percentage)->toBe(40);
});

test('GuarantorResource exposes a dispute_resolution object with these fields when status is settled', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeResolutionSnapshotContext();
    completeDisputeResolutionSnapshotPayment($counterparty, $request, 1010);
    openDisputeForResolutionSnapshot($request, $requester);

    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60);

    $data = GuarantorResource::make($request->fresh())->toArray(request());

    expect($data['dispute_resolution'])->toBe([
        'requester_percentage' => 60,
        'counterparty_percentage' => 40,
        'requester_amount' => '600.00',
        'counterparty_amount' => '404.00',
    ]);
});

test('GuarantorResource omits/nulls dispute_resolution for guarantors never resolved via percentage split (Ended, Cancelled, EndedViaDispute, CancelledViaDispute, Escalated, or still open)', function (
    GuarantorStatusEnum $status,
) {
    $request = GuarantorRequest::factory()->create([
        'status' => $status,
    ]);

    $data = GuarantorResource::make($request)->toArray(request());

    expect($data['dispute_resolution'])->toBeNull();
})->with([
    'ended' => GuarantorStatusEnum::Ended,
    'cancelled' => GuarantorStatusEnum::Cancelled,
    'ended_via_dispute' => GuarantorStatusEnum::EndedViaDispute,
    'cancelled_via_dispute' => GuarantorStatusEnum::CancelledViaDispute,
    'escalated' => GuarantorStatusEnum::Escalated,
    'in_progress' => GuarantorStatusEnum::InProgress,
    'disputed' => GuarantorStatusEnum::Disputed,
]);

test('the persisted amounts exactly match what the notification payload already sends — same numbers, single source of truth', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeResolutionSnapshotContext();
    completeDisputeResolutionSnapshotPayment($counterparty, $request, 1010);
    openDisputeForResolutionSnapshot($request, $requester);

    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60);

    $settled = $request->fresh();

    Notification::assertSentTo($requester, GuarantorDisputeResolvedNotification::class, function ($notification) use ($settled) {
        return $notification->requesterPercentage === $settled->dispute_requester_percentage
            && $notification->counterpartyPercentage === (100 - $settled->dispute_requester_percentage)
            && $notification->requesterAmount === (float) $settled->dispute_requester_amount
            && $notification->counterpartyAmount === (float) $settled->dispute_counterparty_amount;
    });
});

test('existing percentage-split tests still pass — regression', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeResolutionSnapshotContext();
    completeDisputeResolutionSnapshotPayment($counterparty, $request, 1010);
    openDisputeForResolutionSnapshot($request, $requester);

    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Settled)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(600.0)
        ->and((float) $counterparty->wallet->fresh()->balance)->toBe(404.0);
});
