<?php

use App\Models\User;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Http\Controllers\Dashboard\PayoutRequestController;
use Modules\Payout\Models\PayoutRequest;
use Modules\Wallet\Models\WithdrawRequest;

test('GET dashboard/payout-requests with a search query param filters results by the searched term', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $admin = createPayoutDashboardAdmin(['confirm payouts']);

    $matchingUser = User::factory()->create([
        'f_name' => 'SearchableAlpha',
        'l_name' => 'Recipient',
    ]);
    $otherUser = User::factory()->create([
        'f_name' => 'UnrelatedBeta',
        'l_name' => 'Person',
    ]);

    $matchingWithdraw = WithdrawRequest::factory()->for($matchingUser, 'user')->create(['amount' => 100]);
    $otherWithdraw = WithdrawRequest::factory()->for($otherUser, 'user')->create(['amount' => 50]);

    $matching = PayoutRequest::factory()->create([
        'amount' => 100,
        'status' => PayoutStatusEnum::Pending,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $matchingWithdraw->id,
        'recipient_type' => $matchingUser::class,
        'recipient_id' => $matchingUser->id,
        'gateway_reference' => null,
    ]);

    PayoutRequest::factory()->create([
        'amount' => 50,
        'status' => PayoutStatusEnum::Pending,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $otherWithdraw->id,
        'recipient_type' => $otherUser::class,
        'recipient_id' => $otherUser->id,
        'gateway_reference' => null,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([PayoutRequestController::class, 'index'], ['search' => 'SearchableAlpha']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/PayoutRequests/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matching->id)
            ->where('prams.search', 'SearchableAlpha')
        );
});

test('search matches on recipient name and gateway_reference', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $admin = createPayoutDashboardAdmin(['confirm payouts']);

    $user = User::factory()->create([
        'f_name' => 'GatewayOnly',
        'l_name' => 'User',
    ]);
    $provider = createWalletProvider(['name' => 'Acme Provider Co']);

    $userWithdraw = createWithdrawFor($user, ['amount' => 40]);
    $providerWithdraw = createWithdrawFor($provider, ['amount' => 60]);
    $refWithdraw = createWithdrawFor($user, ['amount' => 70]);

    $byProviderName = PayoutRequest::factory()->create([
        'amount' => 60,
        'status' => PayoutStatusEnum::Pending,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $providerWithdraw->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
        'gateway_reference' => 'OTHER-REF-1',
    ]);

    $byGatewayReference = PayoutRequest::factory()->create([
        'amount' => 70,
        'status' => PayoutStatusEnum::Submitted,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $refWithdraw->id,
        'recipient_type' => $user::class,
        'recipient_id' => $user->id,
        'gateway_reference' => 'UNIQUE-BANK-TXN-999',
    ]);

    PayoutRequest::factory()->create([
        'amount' => 40,
        'status' => PayoutStatusEnum::Pending,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $userWithdraw->id,
        'recipient_type' => $user::class,
        'recipient_id' => $user->id,
        'gateway_reference' => 'OTHER-REF-2',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([PayoutRequestController::class, 'index'], ['search' => 'Acme Provider']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $byProviderName->id)
        );

    $this->actingAs($admin, 'admin')
        ->get(action([PayoutRequestController::class, 'index'], ['search' => 'UNIQUE-BANK-TXN-999']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $byGatewayReference->id)
        );
});

test('omitting the search param still returns unfiltered results — regression', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $admin = createPayoutDashboardAdmin(['confirm payouts']);

    $first = PayoutRequest::factory()->create(['status' => PayoutStatusEnum::Pending]);
    $second = PayoutRequest::factory()->create(['status' => PayoutStatusEnum::Submitted]);
    $third = PayoutRequest::factory()->create(['status' => PayoutStatusEnum::Failed]);

    $this->actingAs($admin, 'admin')
        ->get(action([PayoutRequestController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/PayoutRequests/Index')
            ->has('rows.data', 3)
            ->where('rows.data', fn ($rows) => collect($rows)->pluck('id')->sort()->values()->all()
                === collect([$first->id, $second->id, $third->id])->sort()->values()->all())
        );
});
