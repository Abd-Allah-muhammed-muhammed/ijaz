<?php

use App\Http\Controllers\Provider\AuthController;
use App\Http\Controllers\Provider\HomeController;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Controllers\Provider\OrderController;
use Modules\Orders\Http\Controllers\Provider\ProviderChatIndexController;
use Modules\Orders\Models\Order;
use Modules\Wallet\Http\Controllers\Provider\TopUpController;
use Modules\Wallet\Http\Controllers\Provider\WithdrawController;
beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
    withoutWalletLocaleMiddleware();
});

/**
 * Relative paths under resources/js/apps/provider that Task 1 deletes.
 * Kept as a single list so the "zero remaining imports" test stays in sync
 * with the actual cleanup.
 *
 * @return list<string>
 */
function providerDeadWeightDeletedRelativePaths(): array
{
    return [
        'layouts/accounts/AccountPage.tsx',
        'layouts/accounts/AccountHeader.tsx',
        'layouts/accounts/components/Overview.tsx',
        'layouts/accounts/components/settings/Settings.tsx',
        'layouts/accounts/components/settings/SettingsModel.ts',
        'layouts/accounts/components/settings/cards/ProfileDetails.tsx',
        'layouts/accounts/components/settings/cards/SignInMethod.tsx',
        'layouts/accounts/components/settings/cards/ConnectedAccounts.tsx',
        'layouts/accounts/components/settings/cards/EmailPreferences.tsx',
        'layouts/accounts/components/settings/cards/Notifications.tsx',
        'layouts/header/Header.tsx',
        'layouts/header/header-menus/MenuInner.tsx',
        'layouts/header/header-menus/MenuInnerWithSub.tsx',
        'layouts/header/header-menus/MenuItem.tsx',
        'layouts/header/header-menus/MegaMenu.tsx',
        'layouts/header/header-menus/index.ts',
        'layouts/sidebar/SidebarFooter.tsx',
        'pages/TopUpRequests/types.d.ts',
    ];
}

/**
 * @return list<string>
 */
function providerLiveInertiaPageRelativePaths(): array
{
    return [
        'pages/Home.tsx',
        'pages/Auth/LoginPage.tsx',
        'pages/Auth/Profile/Index.tsx',
        'pages/Auth/Profile/wallet.tsx',
        'pages/Orders/Index.tsx',
        'pages/Orders/Recommended.tsx',
        'pages/Orders/Offers.tsx',
        'pages/Orders/Show.tsx',
        'pages/TopUpRequests/Index.tsx',
        'pages/TopUpRequests/Show.tsx',
        'pages/WithdrawRequests/Index.tsx',
        'pages/WithdrawRequests/Show.tsx',
        'pages/Chat/Index.tsx',
        'layouts/ProviderLayout.tsx',
        'layouts/AccountLayout.tsx',
    ];
}

test('the Provider app builds and every sidebar route still renders correctly after removing the flagged dead files', function (): void {
    $provider = createWalletProvider();

    $order = Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ]);

    $topUp = createTopUpFor($provider);
    $withdraw = createWithdrawFor($provider);

    $this->actingAs($provider, 'provider');

    // Sidebar destinations
    $this->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/Home'));

    $this->get(action([OrderController::class, 'new']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/Orders/Recommended'));

    $this->get(action([OrderController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/Orders/Index'));

    $this->get(action([OrderController::class, 'offers']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/Orders/Offers'));

    $this->get(action([AuthController::class, 'statements']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/Auth/Profile/wallet'));

    $this->get(action([TopUpController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/TopUpRequests/Index'));

    $this->get(action([WithdrawController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/WithdrawRequests/Index'));

    $this->get(action(ProviderChatIndexController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/Chat/Index'));

    // Non-sidebar but real Inertia pages that must keep working
    $this->get(action([AuthController::class, 'profile']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/Auth/Profile/Index'));

    $this->get(action([OrderController::class, 'show'], ['order' => $order->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/Orders/Show'));

    $this->get(action([TopUpController::class, 'show'], ['top_up_request' => $topUp->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/TopUpRequests/Show'));

    $this->get(action([WithdrawController::class, 'show'], ['withdraw_request' => $withdraw->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/WithdrawRequests/Show'));

    $this->post(action([AuthController::class, 'logout']));

    $this->get(action([AuthController::class, 'loginForm']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/Auth/LoginPage'));
});

test('ProviderLayout, AccountLayout, and all 15 real Inertia pages have zero remaining imports from any deleted file', function (): void {
    $providerRoot = resource_path('js/apps/provider');

    foreach (providerDeadWeightDeletedRelativePaths() as $relative) {
        expect(file_exists($providerRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative)))
            ->toBeFalse("Expected deleted dead-weight file to be gone: {$relative}");
    }

    // Carve-out: DeactivateAccount must survive — Profile still mounts it.
    expect(file_exists($providerRoot.'/layouts/accounts/components/settings/cards/DeactivateAccount.tsx'))
        ->toBeTrue();

    // Keep the live header-menus tree (Navbar imports from it).
    expect(file_exists($providerRoot.'/layouts/header-menus/HeaderUserMenu.tsx'))
        ->toBeTrue()
        ->and(file_exists($providerRoot.'/layouts/header-menus/HeaderNotificationsMenu.tsx'))
        ->toBeTrue();

    $deletedBasenames = collect(providerDeadWeightDeletedRelativePaths())
        ->map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME))
        ->unique()
        ->values()
        ->all();

    $importNeedlePatterns = [
        'layouts/accounts/AccountPage',
        'layouts/accounts/AccountHeader',
        'layouts/accounts/components/Overview',
        'layouts/accounts/components/settings/Settings',
        'layouts/accounts/components/settings/SettingsModel',
        'layouts/accounts/components/settings/cards/ProfileDetails',
        'layouts/accounts/components/settings/cards/SignInMethod',
        'layouts/accounts/components/settings/cards/ConnectedAccounts',
        'layouts/accounts/components/settings/cards/EmailPreferences',
        'layouts/accounts/components/settings/cards/Notifications',
        'layouts/header/Header',
        'layouts/header/header-menus',
        'layouts/sidebar/SidebarFooter',
        'TopUpRequests/types',
        'SidebarMenuItemWithSub',
    ];

    foreach (providerLiveInertiaPageRelativePaths() as $relative) {
        $absolute = $providerRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        expect(file_exists($absolute))->toBeTrue("Expected live page/layout to still exist: {$relative}");

        $source = file_get_contents($absolute);
        expect($source)->not->toBeFalse();

        foreach ($importNeedlePatterns as $needle) {
            expect($source)->not->toContain(
                $needle,
                "{$relative} still references deleted dead weight via '{$needle}'",
            );
        }

        foreach ($deletedBasenames as $basename) {
            // Avoid false positives on short names that appear in prose/CSS — only flag import-style refs.
            if (preg_match('/from\s+[\'"][^\'"]*'.preg_quote($basename, '/').'[\'"]/', $source) === 1) {
                expect(false)->toBeTrue("{$relative} still imports deleted basename {$basename}");
            }
        }
    }

    // Sidebar must not keep the unused SidebarMenuItemWithSub import.
    $sidebarMain = file_get_contents($providerRoot.'/layouts/sidebar/sidebar-menu/SidebarMenuMain.tsx');
    expect($sidebarMain)->not->toBeFalse()
        ->and($sidebarMain)->not->toContain('SidebarMenuItemWithSub');

    // Withdraw Show must not keep the discovery's commented-out user block.
    $withdrawShow = file_get_contents($providerRoot.'/pages/WithdrawRequests/Show.tsx');
    expect($withdrawShow)->not->toBeFalse()
        ->and($withdrawShow)->not->toContain('{/*<div className="py-3 flex flex-col sm:flex-row sm:items-center gap-2">*/}')
        ->and($withdrawShow)->not->toContain("trans('user')");
});
