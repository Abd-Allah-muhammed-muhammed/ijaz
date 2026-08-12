<?php

use App\Actions\DeviceToken\RegisterDeviceTokenAction;
use App\Models\DeviceToken;
use App\Notifications\ProviderPendingApprovalNotification;
use App\Services\Auth\AdminAuthService;
use App\Services\Auth\ProviderAuthService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Contracts\Repositories\SystemRepositoryInterface;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Models\ProviderType;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Wallet\DTOs\CreateTopUpData;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Notifications\OfflineTopUpPendingReviewNotification;
use Modules\Wallet\Notifications\WithdrawPendingReviewNotification;
use Modules\Wallet\Services\TopUpRequestService;
use Modules\Wallet\Services\WithdrawRequestService;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

test('admin can register a web FCM token, mirroring the Provider flow', function () {
    $admin = createSupportDashboardAdmin(['show supportTicket']);

    $this->actingAs($admin, 'admin')
        ->postJson(route('dashboard.device-tokens.store'), [
            'token' => 'admin-web-fcm-token-abc',
        ])
        ->assertSuccessful();

    expect(DeviceToken::query()->where('token', 'admin-web-fcm-token-abc')->first())
        ->not->toBeNull()
        ->platform->toBe('web')
        ->and($admin->fresh()->deviceTokens()->where('token', 'admin-web-fcm-token-abc')->exists())->toBeTrue();
});

test('admin logout clears only that session web token', function () {
    $admin = createSupportDashboardAdmin(['show supportTicket']);

    app(RegisterDeviceTokenAction::class)->handle($admin, 'admin-mobile-keep', 'android');
    app(RegisterDeviceTokenAction::class)->handle($admin, 'admin-other-web', 'web');
    app(RegisterDeviceTokenAction::class)->handle($admin, 'admin-session-web', 'web');

    $this->actingAs($admin, 'admin');

    $request = Request::create('/dashboard/logout', 'POST');
    $request->setLaravelSession($this->app['session']->driver());
    $request->session()->put('admin_web_fcm_token', 'admin-session-web');

    app(AdminAuthService::class)->logout($request);

    expect($admin->fresh()->deviceTokens()->pluck('token')->sort()->values()->all())
        ->toBe(['admin-mobile-keep', 'admin-other-web'])
        ->and(auth('admin')->check())->toBeFalse();
});

test('a pending withdraw request notifies all admins with the correct permission', function () {
    Notification::fake();

    $withdrawAdmin = createWalletAdmin(['show withdrawRequests', 'edit withdrawRequests']);
    $otherWithdrawAdmin = createWalletAdmin(['show withdrawRequests']);
    $unrelatedAdmin = createWalletAdmin(['show topUpRequests']);

    $provider = createWalletProvider();
    fundWallet($provider, 500);

    app(WithdrawRequestService::class)->create(
        $provider,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );

    Notification::assertSentTo($withdrawAdmin, WithdrawPendingReviewNotification::class);
    Notification::assertSentTo($otherWithdrawAdmin, WithdrawPendingReviewNotification::class);
    Notification::assertNotSentTo($unrelatedAdmin, WithdrawPendingReviewNotification::class);
});

test('a pending offline top-up request notifies all admins', function () {
    Notification::fake();

    $topUpAdmin = createWalletAdmin(['show topUpRequests', 'edit topUpRequests']);
    $otherTopUpAdmin = createWalletAdmin(['show topUpRequests']);
    $unrelatedAdmin = createWalletAdmin(['show withdrawRequests']);

    $user = createWalletUser();

    app(TopUpRequestService::class)->create(
        $user,
        new CreateTopUpData(
            amount: 150,
            paymentMethod: PaymentMethodEnum::Offline,
            paymentDriver: null,
            transactionImage: 'media/test-receipt.png',
            userNotes: null,
        ),
    );

    Notification::assertSentTo($topUpAdmin, OfflineTopUpPendingReviewNotification::class);
    Notification::assertSentTo($otherTopUpAdmin, OfflineTopUpPendingReviewNotification::class);
    Notification::assertNotSentTo($unrelatedAdmin, OfflineTopUpPendingReviewNotification::class);
});

test('a provider pending approval notifies all admins', function () {
    Notification::fake();
    Storage::fake('public');

    $providerAdmin = createSupportDashboardAdmin(['show providers', 'process providers']);
    $otherProviderAdmin = createSupportDashboardAdmin(['show providers']);
    $unrelatedAdmin = createSupportDashboardAdmin(['show users']);

    $type = ProviderType::query()->create(['image' => 'media/test-type.png']);
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);

    $request = Request::create('/provider/register', 'POST', [], [], [
        'logo' => UploadedFile::fake()->image('logo.png'),
    ]);

    $result = app(ProviderAuthService::class)->register([
        'name' => 'Pending Co',
        'iban' => fake()->unique()->iban('SA'),
        'phone' => '512345678',
        'email' => 'pending-provider@example.com',
        'provider_type_id' => $type->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'password' => 'password',
        'categories' => [],
    ], $request);

    expect($result->success)->toBeTrue();

    Notification::assertSentTo($providerAdmin, ProviderPendingApprovalNotification::class);
    Notification::assertSentTo($otherProviderAdmin, ProviderPendingApprovalNotification::class);
    Notification::assertNotSentTo($unrelatedAdmin, ProviderPendingApprovalNotification::class);
});

test('admin receives a chat-updated toast trigger for ticket messages when not viewing that conversation', function () {
    ['conversation' => $conversation, 'user' => $user] = createTicketSupportConversation();

    $system = app(SystemRepositoryInterface::class)->findOrCreateDefault();

    // User → System ticket message path: ChatUpdatedEvent targets systems.1.
    // MasterLayout listens there and toasts when currentConversation differs.
    $event = new ChatUpdatedEvent($conversation, $user, $system);

    $channelNames = collect($event->broadcastOn())
        ->map(fn (Channel $channel): string => $channel->name);

    expect($channelNames->all())->toContain('private-systems.1');
});
