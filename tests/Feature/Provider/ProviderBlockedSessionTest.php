<?php

use App\DTOs\Provider\UpdateProviderStatusDTO;
use App\Enums\Providers\ProviderStatusEnum;
use App\Http\Controllers\Provider\HomeController;
use App\Services\Provider\ProviderManagementService;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

/**
 * Providers authenticate via the session guard (auth:provider) and cannot hold
 * Sanctum tokens (no HasApiTokens on Provider). The session is therefore the
 * provider equivalent of the User tokens revoked by UpdateUserStatusAction:
 * blocking must terminate any already-active session, not just gate the next
 * login attempt (LoginRequest::authenticate()).
 */
test('blocking a provider revokes their active session', function () {
    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful();

    app(ProviderManagementService::class)->updateStatus($provider, new UpdateProviderStatusDTO(
        status: ProviderStatusEnum::Blocked->value,
        blockDays: 5,
        blockReason: 'policy violation',
    ));

    $this->get(action(HomeController::class))
        ->assertRedirect(route('provider.login'));

    expect(auth('provider')->check())->toBeFalse();
});

test('an approved provider keeps their session across requests', function () {
    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful();

    $this->get(action(HomeController::class))->assertSuccessful();

    expect(auth('provider')->check())->toBeTrue();
});
