<?php

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Modules\Guarantor\Actions\Guarantor\AcceptGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction;
use Modules\Guarantor\Actions\Guarantor\OpenGuarantorDisputeAction;
use Modules\Guarantor\Actions\Guarantor\RejectGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorStatusAction;
use Modules\Guarantor\Actions\Guarantor\WithdrawGuarantorAction;
use Modules\Guarantor\DTOs\GuarantorAcceptUploadData;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Http\Resources\Api\GuarantorParticipantResource;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{0: User|Provider|Admin, 1: string}
 */
function guarantorActorNameCases(): array
{
    $user = User::factory()->create([
        'f_name' => 'Ada',
        'l_name' => 'Lovelace',
    ]);
    $provider = createWalletProvider(['name' => 'Acme Guarantor Co']);
    $admin = Admin::query()->create([
        'name' => 'Dispute Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    return [
        'user' => [$user, 'Ada Lovelace'],
        'provider' => [$provider, 'Acme Guarantor Co'],
        'admin' => [$admin, 'Dispute Admin'],
    ];
}

test('a guarantor_status_histories row captures actor_name = $actor->name at creation, for User, Provider, and Admin actors', function () {
    $request = GuarantorRequest::factory()->create();

    foreach (guarantorActorNameCases() as $label => [$actor, $expectedName]) {
        expect($actor->name)->toBe($expectedName, "failed pre-check for {$label}");

        $history = app(LogGuarantorStatusHistoryAction::class)->handle(
            $request,
            $actor,
            GuarantorStatusEnum::PendingAdmin->value,
            GuarantorStatusEnum::Accepted->value,
            reason: "actor-{$label}",
        );

        expect($history->actor_name)->toBe($expectedName)
            ->and($history->actor_id)->toBe($actor->getKey())
            ->and($history->actor_type)->toBe($actor::class);
    }
});

test('actor_name remains correct even if the underlying actor account is later deleted', function () {
    $request = GuarantorRequest::factory()->create();
    $admin = Admin::query()->create([
        'name' => 'Soon Deleted Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $history = app(LogGuarantorStatusHistoryAction::class)->handle(
        $request,
        $admin,
        GuarantorStatusEnum::PendingAdmin->value,
        GuarantorStatusEnum::ApprovedByAdmin->value,
    );

    $historyId = $history->id;
    $admin->delete();

    $reloaded = GuarantorStatusHistory::query()->find($historyId);

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->actor_name)->toBe('Soon Deleted Admin')
        ->and($reloaded->actor)->toBeNull();
});

test('every existing status-history-logging call site populates actor_name correctly — full regression across End, Cancel, Dispute, Withdraw, Accept, Reject, admin approve/reject', function () {
    $admin = Admin::query()->create([
        'name' => 'Call Site Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    // Admin approve
    $toApprove = GuarantorRequest::factory()->pendingAdmin()->create();
    app(UpdateGuarantorStatusAction::class)->handle(
        $toApprove,
        new UpdateGuarantorStatusData(status: GuarantorStatusEnum::ApprovedByAdmin),
        $admin,
        'admin',
    );
    expectLatestHistoryActorName($toApprove, GuarantorStatusEnum::ApprovedByAdmin->value, $admin->name);

    // Admin reject
    $toReject = GuarantorRequest::factory()->pendingAdmin()->create();
    app(UpdateGuarantorStatusAction::class)->handle(
        $toReject,
        new UpdateGuarantorStatusData(
            status: GuarantorStatusEnum::RejectedByAdmin,
            reason: 'Incomplete documents',
        ),
        $admin,
        'admin',
    );
    expectLatestHistoryActorName($toReject, GuarantorStatusEnum::RejectedByAdmin->value, $admin->name);

    // Counterparty accept
    $toAccept = GuarantorRequest::factory()->approvedByAdmin()->create();
    $counterparty = $toAccept->counterparty;
    app(AcceptGuarantorAction::class)->handle(
        $toAccept,
        $counterparty,
        new GuarantorAcceptUploadData(
            signature: UploadedFile::fake()->create('cp-signature.pdf', 100, 'application/pdf'),
        ),
    );
    expectLatestHistoryActorName($toAccept, GuarantorStatusEnum::Accepted->value, $counterparty->name);

    // Counterparty reject
    $toCpReject = GuarantorRequest::factory()->approvedByAdmin()->create();
    $cpRejectActor = $toCpReject->counterparty;
    app(RejectGuarantorAction::class)->handle(
        $toCpReject,
        $cpRejectActor,
        'counterparty',
        'Not interested',
    );
    expectLatestHistoryActorName($toCpReject, GuarantorStatusEnum::Rejected->value, $cpRejectActor->name);

    // Requester withdraw
    $toWithdraw = GuarantorRequest::factory()->approvedByAdmin()->create();
    $withdrawer = $toWithdraw->requester;
    app(WithdrawGuarantorAction::class)->handle(
        $toWithdraw,
        $withdrawer,
        'requester',
        'Changed mind',
    );
    expectLatestHistoryActorName($toWithdraw, GuarantorStatusEnum::Withdrawn->value, $withdrawer->name);

    // Party dispute
    $toDispute = GuarantorRequest::factory()->inProgress()->create();
    $disputer = $toDispute->requester;
    app(OpenGuarantorDisputeAction::class)->handle(
        $toDispute,
        $disputer,
        'requester',
        'Goods not as agreed',
    );
    expectLatestHistoryActorName($toDispute, GuarantorStatusEnum::Disputed->value, $disputer->name);

    // Party end
    $toEnd = GuarantorRequest::factory()->inProgress()->create();
    $ender = $toEnd->requester;
    app(EndGuarantorAction::class)->handle($toEnd, $ender, 'requester');
    expectLatestHistoryActorName($toEnd, GuarantorStatusEnum::Ended->value, $ender->name);

    // Admin cancel
    $toCancel = GuarantorRequest::factory()->accepted()->create();
    app(CancelGuarantorAction::class)->handle(
        $toCancel,
        'Client withdrew from the contract',
        null,
        $admin,
    );
    expectLatestHistoryActorName($toCancel, GuarantorStatusEnum::Cancelled->value, $admin->name);
});

test('GuarantorParticipantResource still returns the correct name for User/Provider/Admin after removing the redundant fallback — regression', function () {
    $user = User::factory()->create([
        'f_name' => 'Grace',
        'l_name' => 'Hopper',
    ]);
    $provider = createWalletProvider(['name' => 'Provider Display Name']);
    $admin = Admin::query()->create([
        'name' => 'Admin Display Name',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    expect(GuarantorParticipantResource::make($user)->toArray(Request::create('/'))['name'])
        ->toBe('Grace Hopper')
        ->and(GuarantorParticipantResource::make($provider)->toArray(Request::create('/'))['name'])
        ->toBe('Provider Display Name')
        ->and(GuarantorParticipantResource::make($admin)->toArray(Request::create('/'))['name'])
        ->toBe('Admin Display Name');
});

function expectLatestHistoryActorName(GuarantorRequest $request, string $toStatus, string $expectedName): void
{
    $history = GuarantorStatusHistory::query()
        ->where('guarantor_request_id', $request->id)
        ->where('to_status', $toStatus)
        ->latest('created_at')
        ->latest('id')
        ->first();

    expect($history)->not->toBeNull("missing history for to_status={$toStatus}")
        ->and($history->actor_name)->toBe($expectedName);
}
