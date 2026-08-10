<?php

use App\Actions\DeviceToken\ClearAllDeviceTokensAction;
use App\Actions\DeviceToken\RegisterDeviceTokenAction;
use App\Actions\Provider\UpdateProviderStatusAction;
use App\Actions\User\UpdateUserStatusAction;
use App\DTOs\Provider\UpdateProviderStatusDTO;
use App\DTOs\User\UpdateUserStatusDTO;
use App\Enums\OperationStatusEnum;
use App\Enums\Providers\ProviderStatusEnum;
use App\Enums\Users\UserStatusEnum;
use App\Models\Admin;
use App\Models\User;
use App\Notifications\AccountStatusChangedNotification;
use App\Notifications\DomainNotification;
use App\Notifications\StatusChangedNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Facades\Notification;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\Specialization;
use Modules\Classifieds\Actions\CarAdvisement\UpdateCarAdvisementStatusForDashboardAction;
use Modules\Classifieds\Actions\ElectronicAdvisement\UpdateElectronicAdvisementStatusForDashboardAction;
use Modules\Classifieds\Actions\InstituteAdvisement\UpdateInstituteAdvisementStatusForDashboardAction;
use Modules\Classifieds\Actions\PropertyAdvisement\UpdatePropertyAdvisementStatusForDashboardAction;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\ElectronicConditionEnum;
use Modules\Classifieds\Enums\InstituteTypeEnum;
use Modules\Classifieds\Enums\StudyLevelEnum;
use Modules\Classifieds\Enums\StudyTypeEnum;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Classifieds\Notifications\AdvisementStatusChangedNotification;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Orders\Models\Order;
use Modules\Reviews\Actions\Review\CreateOrUpdateReviewAction;
use Modules\Reviews\DTOs\CreateReviewDTO;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Notifications\ReviewReceivedNotification;
use Modules\Support\Actions\TicketSupport\UpdateTicketSupportStatusAction;
use Modules\Support\DTOs\UpdateTicketSupportStatusDTO;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Models\TicketSupport;
use Modules\Support\Notifications\TicketStatusChangedNotification;
use Modules\Wallet\Actions\Withdraw\UpdateWithdrawStatusForDashboardAction;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Notifications\WithdrawStatusChangedNotification;

function statusChangedNotifiableUser(array $attributes = []): User
{
    return User::factory()->create(['language' => 'en', ...$attributes]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createPendingElectronicAdvisementForOwner(User $owner, array $attributes = []): ElectronicAdvisement
{
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $category = DeviceCategory::query()->create(['icon' => 'icons/test.png']);
    $category->translateOrNew('en')->title = 'Phones';
    $category->save();
    $brand = ElectronicBrand::query()->create(['image' => 'brands/test.png', 'is_active' => true]);
    $brand->translateOrNew('en')->name = 'Test Brand';
    $brand->save();

    return ElectronicAdvisement::query()->create([
        'title' => 'Electronic item',
        'description' => 'A device',
        'image' => 'media/test.png',
        'status' => AdvisementStatusEnum::PENDING,
        'condition' => ElectronicConditionEnum::NEW,
        'price' => 50,
        'show_price' => true,
        'phone' => '966501234567',
        'user_type' => User::class,
        'user_id' => $owner->id,
        'device_category_id' => $category->id,
        'electronic_brand_id' => $brand->id,
        'city_id' => $city->id,
        'region_id' => $region->id,
        'options' => [],
        ...$attributes,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createPendingInstituteAdvisementForOwner(User $owner, array $attributes = []): InstituteAdvisement
{
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $specialization = Specialization::factory()->create();

    return InstituteAdvisement::query()->create([
        'title' => 'Institute course',
        'description' => 'A course',
        'image' => 'media/test.png',
        'status' => AdvisementStatusEnum::PENDING,
        'price' => 50,
        'type' => InstituteTypeEnum::INSTITUTE,
        'study_type' => StudyTypeEnum::ONSITE,
        'study_level' => StudyLevelEnum::CERTIFICATE,
        'phone' => '966501234567',
        'user_type' => User::class,
        'user_id' => $owner->id,
        'specialization_id' => $specialization->id,
        'city_id' => $city->id,
        'region_id' => $region->id,
        'options' => [],
        ...$attributes,
    ]);
}

describe('StatusChangedNotification contracts', function (): void {
    it('locks WithdrawStatusChangedNotification channel outputs', function (): void {
        $owner = statusChangedNotifiableUser();
        $withdraw = WithdrawRequest::factory()->create([
            'user_id' => $owner->id,
            'user_type' => User::class,
            'wallet_id' => $owner->wallet->id,
            'amount' => 25,
        ]);
        $notification = new WithdrawStatusChangedNotification($withdraw, OperationStatusEnum::Approved->value);

        expect($notification)->toBeInstanceOf(StatusChangedNotification::class)
            ->and($notification)->toBeInstanceOf(ShouldBroadcastNow::class)
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->via($owner))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('withdraw status approved')
            ->and($notification->toArray($owner))->toBe([
                'title_translated_key' => 'withdraw_status_approved_title',
                'body_translated_key' => 'withdraw_status_approved_body',
                'translated_attributes' => [],
                'status' => 'approved',
                'withdraw_request_id' => $withdraw->id,
            ]);
    });

    it('locks ReviewReceivedNotification outside StatusChangedNotification', function (): void {
        $notification = new ReviewReceivedNotification(
            new Review([
                'id' => 1,
                'rating' => 5,
                'operation_type' => Order::class,
                'operation_id' => 1,
            ]),
        );

        expect($notification)->toBeInstanceOf(DomainNotification::class)
            ->and($notification)->not->toBeInstanceOf(StatusChangedNotification::class)
            ->and($notification->broadcastType())->toBe('review received')
            ->and($notification->via(statusChangedNotifiableUser()))->toBe(['database', 'broadcast', 'firebase']);
    });
});

test('withdraw approved/rejected notifies the wallet owner with the correct payload', function (string $status): void {
    Notification::fake();

    $owner = statusChangedNotifiableUser();
    $owner->wallet->update(['balance' => 500, 'pending_debit' => 100]);
    $admin = Admin::query()->create([
        'name' => 'Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $withdraw = WithdrawRequest::factory()->create([
        'user_id' => $owner->id,
        'user_type' => User::class,
        'wallet_id' => $owner->wallet->id,
        'amount' => 100,
        'status' => OperationStatusEnum::Pending,
    ]);

    app(UpdateWithdrawStatusForDashboardAction::class)->handle(
        $withdraw,
        $status,
        'notes',
        $admin->id,
    );

    Notification::assertSentTo($owner, WithdrawStatusChangedNotification::class, function (WithdrawStatusChangedNotification $notification) use ($withdraw, $status): bool {
        return $notification->status === $status
            && $notification->withdrawRequest->is($withdraw)
            && $notification->toArray($withdraw->user)['withdraw_request_id'] === $withdraw->id;
    });
})->with([
    OperationStatusEnum::Approved->value,
    OperationStatusEnum::Rejected->value,
]);

test('support ticket resolved/closed notifies the ticket owner', function (): void {
    Notification::fake();

    $owner = statusChangedNotifiableUser();
    $ticket = TicketSupport::factory()->create([
        'user_id' => $owner->id,
        'user_type' => User::class,
        'status' => TicketSupportStatusEnum::Open,
    ]);

    app(UpdateTicketSupportStatusAction::class)->handle(
        $ticket,
        new UpdateTicketSupportStatusDTO(TicketSupportStatusEnum::Closed),
    );

    Notification::assertSentTo($owner, TicketStatusChangedNotification::class, function (TicketStatusChangedNotification $notification) use ($ticket): bool {
        return $notification->status === TicketSupportStatusEnum::Closed->value
            && $notification->ticket->is($ticket);
    });
});

test('advisement published/rejected notifies the owner for all 4 classifieds types', function (string $kind, string $status): void {
    Notification::fake();

    $owner = statusChangedNotifiableUser();

    match ($kind) {
        'car' => app(UpdateCarAdvisementStatusForDashboardAction::class)->handle(
            CarAdvisement::factory()->pending()->create([
                'user_id' => $owner->id,
                'user_type' => User::class,
            ]),
            AdvisementStatusEnum::from($status),
        ),
        'property' => app(UpdatePropertyAdvisementStatusForDashboardAction::class)->handle(
            PropertyAdvisement::factory()->create([
                'status' => AdvisementStatusEnum::PENDING,
                'user_id' => $owner->id,
                'user_type' => User::class,
            ]),
            AdvisementStatusEnum::from($status),
        ),
        'electronic' => app(UpdateElectronicAdvisementStatusForDashboardAction::class)->handle(
            createPendingElectronicAdvisementForOwner($owner),
            AdvisementStatusEnum::from($status),
        ),
        'institute' => app(UpdateInstituteAdvisementStatusForDashboardAction::class)->handle(
            createPendingInstituteAdvisementForOwner($owner),
            AdvisementStatusEnum::from($status),
        ),
    };

    Notification::assertSentTo($owner, AdvisementStatusChangedNotification::class, function (AdvisementStatusChangedNotification $notification) use ($kind, $status): bool {
        return $notification->advisementKind === $kind
            && $notification->status === $status;
    });
})->with([
    ['car', AdvisementStatusEnum::PUBLISHED->value],
    ['car', AdvisementStatusEnum::REJECTED->value],
    ['property', AdvisementStatusEnum::PUBLISHED->value],
    ['property', AdvisementStatusEnum::REJECTED->value],
    ['electronic', AdvisementStatusEnum::PUBLISHED->value],
    ['electronic', AdvisementStatusEnum::REJECTED->value],
    ['institute', AdvisementStatusEnum::PUBLISHED->value],
    ['institute', AdvisementStatusEnum::REJECTED->value],
]);

test('account approved/rejected/blocked/suspended notifies the account owner, notification fires before device tokens are cleared', function (): void {
    Notification::fake();

    $user = statusChangedNotifiableUser(['status' => UserStatusEnum::Active]);
    app(RegisterDeviceTokenAction::class)->handle($user, 'status-change-token');
    expect($user->deviceTokens()->count())->toBe(1);

    $this->mock(ClearAllDeviceTokensAction::class, function ($mock): void {
        $mock->shouldReceive('handle')->once()->withArgs(function (User $tokenable): bool {
            // Ordering proof: notify must already have been recorded before tokens clear.
            Notification::assertSentTo($tokenable, AccountStatusChangedNotification::class);
            expect($tokenable->deviceTokens()->count())->toBe(1);
            $tokenable->deviceTokens()->delete();

            return true;
        });
    });

    app(UpdateUserStatusAction::class)->handle(
        $user,
        new UpdateUserStatusDTO(
            status: UserStatusEnum::Blocked->value,
            blockDays: 3,
            blockReason: 'policy',
        ),
    );

    expect($user->fresh()->deviceTokens()->count())->toBe(0);

    $cases = [
        [ProviderStatusEnum::Pending, ProviderStatusEnum::Approved->value, null, null],
        [ProviderStatusEnum::Pending, ProviderStatusEnum::Rejected->value, null, null],
        [ProviderStatusEnum::Approved, ProviderStatusEnum::Suspended->value, null, null],
        [ProviderStatusEnum::Approved, ProviderStatusEnum::Blocked->value, 1, 'blocked'],
    ];

    foreach ($cases as [$from, $to, $blockDays, $blockReason]) {
        $provider = createWalletProvider(['status' => $from]);

        app(UpdateProviderStatusAction::class)->handle(
            $provider,
            new UpdateProviderStatusDTO(
                status: $to,
                blockDays: $blockDays,
                blockReason: $blockReason,
            ),
        );

        Notification::assertSentTo($provider, AccountStatusChangedNotification::class, function (AccountStatusChangedNotification $notification) use ($to): bool {
            return $notification->status === $to;
        });
    }
});

test('a new or updated review notifies the reviewee', function (): void {
    Notification::fake();

    $reviewer = statusChangedNotifiableUser();
    $reviewee = createWalletProvider();
    $order = Order::factory()->create([
        'user_id' => $reviewer->id,
        'provider_id' => $reviewee->id,
    ]);

    $action = app(CreateOrUpdateReviewAction::class);

    $action->handle(new CreateReviewDTO(
        reviewer: $reviewer,
        reviewee: $reviewee,
        operation: $order,
        rating: 5,
        comment: 'Great',
    ));

    Notification::assertSentTo($reviewee, ReviewReceivedNotification::class);

    $action->handle(new CreateReviewDTO(
        reviewer: $reviewer,
        reviewee: $reviewee,
        operation: $order,
        rating: 4,
        comment: 'Updated',
    ));

    Notification::assertSentToTimes($reviewee, ReviewReceivedNotification::class, 2);
});

test('intermediate/non-final status transitions do not trigger a notification', function (): void {
    Notification::fake();

    $owner = statusChangedNotifiableUser();

    $ticket = TicketSupport::factory()->create([
        'user_id' => $owner->id,
        'user_type' => User::class,
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    app(UpdateTicketSupportStatusAction::class)->handle(
        $ticket,
        new UpdateTicketSupportStatusDTO(TicketSupportStatusEnum::Open),
    );

    Notification::assertNotSentTo($owner, TicketStatusChangedNotification::class);

    $advisement = CarAdvisement::factory()->pending()->create([
        'user_id' => $owner->id,
        'user_type' => User::class,
    ]);

    app(UpdateCarAdvisementStatusForDashboardAction::class)->handle(
        $advisement,
        AdvisementStatusEnum::CLOSED,
    );

    Notification::assertNotSentTo($owner, AdvisementStatusChangedNotification::class);

    $provider = createWalletProvider(['status' => ProviderStatusEnum::Pending]);
    app(UpdateProviderStatusAction::class)->handle(
        $provider,
        new UpdateProviderStatusDTO(
            status: ProviderStatusEnum::Pending->value,
            blockDays: null,
            blockReason: null,
        ),
    );

    Notification::assertNotSentTo($provider, AccountStatusChangedNotification::class);
});
