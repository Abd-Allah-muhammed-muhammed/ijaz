<?php

use Modules\Wallet\Http\Controllers\Dashboard\TopUpRequestController;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController;

test('admin can search top-up requests by user name/phone', function (): void {
    withoutWalletLocaleMiddleware();

    $admin = createWalletAdmin(['show topUpRequests']);
    $match = createWalletUser([
        'f_name' => 'SearchableTopUp',
        'l_name' => 'Owner',
        'phone' => '0501112233',
    ]);
    $other = createWalletUser([
        'f_name' => 'OtherTopUp',
        'l_name' => 'Person',
        'phone' => '0509998877',
    ]);

    $matchingTopUp = createTopUpFor($match);
    createTopUpFor($other);

    $this->actingAs($admin, 'admin')
        ->get(action([TopUpRequestController::class, 'index'], ['search' => 'SearchableTopUp']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/TopUpRequests/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matchingTopUp->id)
        );

    $this->actingAs($admin, 'admin')
        ->get(action([TopUpRequestController::class, 'index'], ['search' => '0501112233']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matchingTopUp->id)
        );
})->skip('Admin top-up UI paused — see chore/provider-topup-pause');

test('admin can search withdraw requests by user name/phone', function (): void {
    withoutWalletLocaleMiddleware();

    $admin = createWalletAdmin(['show withdrawRequests']);
    $match = createWalletUser([
        'f_name' => 'SearchableWithdraw',
        'l_name' => 'Owner',
        'phone' => '0502223344',
    ]);
    $other = createWalletUser([
        'f_name' => 'OtherWithdraw',
        'l_name' => 'Person',
        'phone' => '0508887766',
    ]);

    fundWallet($match, 200);
    fundWallet($other, 200);

    $matchingWithdraw = createWithdrawFor($match);
    createWithdrawFor($other);

    $this->actingAs($admin, 'admin')
        ->get(action([WithdrawRequestController::class, 'index'], ['search' => 'SearchableWithdraw']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/WithdrawRequests/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matchingWithdraw->id)
        );

    $this->actingAs($admin, 'admin')
        ->get(action([WithdrawRequestController::class, 'index'], ['search' => '0502223344']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matchingWithdraw->id)
        );
});
