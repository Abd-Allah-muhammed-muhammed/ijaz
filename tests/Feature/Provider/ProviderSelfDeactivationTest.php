<?php

use App\Enums\Providers\ProviderStatusEnum;
use App\Http\Controllers\Provider\AuthController;
use App\Http\Controllers\Provider\HomeController;
use App\Http\Middleware\EnsureProviderIsApprovedMiddleware;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Models\Provider;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
});

test('a provider can self-deactivate their own account, transitioning to a new self_deactivated status', function (): void {
    $provider = createWalletProvider(['status' => ProviderStatusEnum::Approved]);

    $this->actingAs($provider, 'provider')
        ->post(action([AuthController::class, 'deactivate']), [
            'confirmed' => true,
        ])
        ->assertRedirect(route('provider.login'));

    $provider->refresh();

    expect($provider->status)->toBe(ProviderStatusEnum::SelfDeactivated)
        ->and(auth('provider')->check())->toBeFalse();
});

test('a self-deactivated provider is rejected by EnsureProviderIsApprovedMiddleware on their next request, same as suspended/blocked', function (): void {
    $provider = createWalletProvider(['status' => ProviderStatusEnum::Approved]);

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful();

    $this->actingAs($provider, 'provider')
        ->post(action([AuthController::class, 'deactivate']), [
            'confirmed' => true,
        ])
        ->assertRedirect(route('provider.login'));

    // Re-authenticate session as if middleware must gate a still-open session
    // after status flip (mirrors ProviderBlockedSessionTest pattern).
    $this->actingAs($provider->fresh(), 'provider')
        ->get(action(HomeController::class))
        ->assertRedirect(route('provider.login'));

    expect(auth('provider')->check())->toBeFalse();
});

test('self-deactivation does not touch/cancel/hide any of the provider\'s existing orders, offers, or guarantor requests — regression', function (): void {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: [
            'status' => OrderStatusEnum::InProgress,
            'provider_id' => null,
        ],
        offerAttrs: [
            'status' => OfferStatusEnum::Pending,
        ],
    );

    // Bind the order to this provider the way live Accepted flows do, without
    // running accept-side effects — we only need durable rows to regress against.
    $order->forceFill([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ])->save();

    $guarantor = GuarantorRequest::factory()->create([
        'requester_type' => Provider::class,
        'requester_id' => $provider->id,
    ]);

    $orderSnapshot = [
        'id' => $order->id,
        'status' => $order->fresh()->status->value,
        'provider_id' => $order->fresh()->provider_id,
    ];
    $offerSnapshot = [
        'id' => $offer->id,
        'status' => $offer->fresh()->status->value,
        'provider_id' => $offer->fresh()->provider_id,
    ];
    $guarantorSnapshot = [
        'id' => $guarantor->id,
        'status' => $guarantor->fresh()->status->value,
        'requester_id' => $guarantor->fresh()->requester_id,
        'deleted_at' => $guarantor->fresh()->deleted_at,
    ];

    $this->actingAs($provider, 'provider')
        ->post(action([AuthController::class, 'deactivate']), [
            'confirmed' => true,
        ])
        ->assertRedirect(route('provider.login'));

    $orderAfter = Order::query()->findOrFail($orderSnapshot['id']);
    $offerAfter = OrderOffer::query()->findOrFail($offerSnapshot['id']);
    $guarantorAfter = GuarantorRequest::withTrashed()->findOrFail($guarantorSnapshot['id']);

    expect($orderAfter->status->value)->toBe($orderSnapshot['status'])
        ->and($orderAfter->provider_id)->toBe($orderSnapshot['provider_id'])
        ->and($offerAfter->status->value)->toBe($offerSnapshot['status'])
        ->and($offerAfter->provider_id)->toBe($offerSnapshot['provider_id'])
        ->and($guarantorAfter->status->value)->toBe($guarantorSnapshot['status'])
        ->and($guarantorAfter->requester_id)->toBe($guarantorSnapshot['requester_id'])
        ->and($guarantorAfter->deleted_at)->toBe($guarantorSnapshot['deleted_at']);
});

test('an admin viewing the provider list can distinguish self_deactivated from admin-initiated suspended/blocked', function (): void {
    $selfDeactivated = createWalletProvider(['status' => ProviderStatusEnum::SelfDeactivated]);
    $suspended = createWalletProvider(['status' => ProviderStatusEnum::Suspended]);
    $blocked = createWalletProvider(['status' => ProviderStatusEnum::Blocked]);

    $selfPayload = ProviderResource::make($selfDeactivated)->resolve();
    $suspendedPayload = ProviderResource::make($suspended)->resolve();
    $blockedPayload = ProviderResource::make($blocked)->resolve();

    expect($selfPayload['status']['value'])->toBe(ProviderStatusEnum::SelfDeactivated->value)
        ->and($selfPayload['status']['label'])->toBe(ProviderStatusEnum::SelfDeactivated->label())
        ->and($selfPayload['status']['value'])->not->toBe(ProviderStatusEnum::Suspended->value)
        ->and($selfPayload['status']['value'])->not->toBe(ProviderStatusEnum::Blocked->value)
        ->and($selfPayload['status']['label'])->not->toBe($suspendedPayload['status']['label'])
        ->and($selfPayload['status']['label'])->not->toBe($blockedPayload['status']['label'])
        ->and($suspendedPayload['status']['value'])->toBe(ProviderStatusEnum::Suspended->value)
        ->and($blockedPayload['status']['value'])->toBe(ProviderStatusEnum::Blocked->value);
});

test('self-deactivation requires the provider to currently be Approved — rejected otherwise with a clear error', function (): void {
    // Bypass the Approved session gate so we can exercise the Action's own
    // status guard (e.g. race where status flipped after the page loaded).
    $this->withoutMiddleware(EnsureProviderIsApprovedMiddleware::class);

    $provider = createWalletProvider(['status' => ProviderStatusEnum::Pending]);

    $this->actingAs($provider, 'provider')
        ->from(route('provider.profile'))
        ->post(action([AuthController::class, 'deactivate']), [
            'confirmed' => true,
        ])
        ->assertRedirect(route('provider.profile'))
        ->assertSessionHasErrors('status');

    expect($provider->fresh()->status)->toBe(ProviderStatusEnum::Pending);
});
