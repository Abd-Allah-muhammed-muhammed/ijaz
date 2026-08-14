<?php

use App\Http\Controllers\Provider\AuthController;
use Modules\Wallet\Http\Controllers\Provider\TopUpController;
use Modules\Wallet\Http\Controllers\Provider\WithdrawController;

test('provider can search top-up requests by transaction id', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    $matching = createTopUpFor($provider, [
        'transaction_id' => 'TOPUP-TXN-MATCH-001',
    ]);
    createTopUpFor($provider, [
        'transaction_id' => 'TOPUP-TXN-OTHER-999',
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action([TopUpController::class, 'index'], [
            'search' => '#TOPUP-TXN-MATCH-001',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/TopUpRequests/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matching->id)
        );
});

test('provider can search withdraw requests by transaction id', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    fundWallet($provider, 200);

    $matching = createWithdrawFor($provider, ['amount' => 50]);
    $matching->forceFill(['transaction_id' => 'WD-TXN-MATCH-001'])->save();

    $other = createWithdrawFor($provider, ['amount' => 40]);
    $other->forceFill(['transaction_id' => 'WD-TXN-OTHER-999'])->save();

    $this->actingAs($provider, 'provider')
        ->get(action([WithdrawController::class, 'index'], [
            'search' => '#WD-TXN-MATCH-001',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/WithdrawRequests/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matching->id)
        );
});

test('provider can search wallet statements by reference number copied with a leading #', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    fundWallet($provider, 100);

    $transaction = $provider->wallet->transactions()->latest()->first();

    expect($transaction)->not->toBeNull();

    $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'statements'], [
            'search' => '#'.$transaction->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Auth/Profile/wallet')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.id', $transaction->id)
        );
});

test('submitting an empty search value removes the search query parameter and returns the full unfiltered transaction list', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    fundWallet($provider, 100);
    fundWallet($provider, 50);

    $matching = $provider->wallet->transactions()->latest()->first();

    expect($matching)->not->toBeNull();
    expect($provider->wallet->transactions()->count())->toBe(2);

    $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'statements'], [
            'search' => '#'.$matching->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Auth/Profile/wallet')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.id', $matching->id)
        );

    $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'statements'], [
            'search' => '',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Auth/Profile/wallet')
            ->has('transactions.data', 2)
        );

    $source = file_get_contents(resource_path('js/apps/provider/pages/Auth/Profile/wallet.tsx'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('visitWithFilters')
        ->and($source)->toContain('applyFilterParam')
        ->and($source)->not->toContain('router.reload');
});
