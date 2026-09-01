<?php

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use App\Services\Firebase\DTO\FirebaseNotificationContent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Chat\Infrastructure\Notifications\NewMessageSentNotification;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorWithdrawnNotificationAudience;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorAcceptedNotification;
use Modules\Guarantor\Notifications\GuarantorAdminApprovedNotification;
use Modules\Guarantor\Notifications\GuarantorAdminRejectedNotification;
use Modules\Guarantor\Notifications\GuarantorCancelledNotification;
use Modules\Guarantor\Notifications\GuarantorCounterpartyRejectedNotification;
use Modules\Guarantor\Notifications\GuarantorCreatedNotification;
use Modules\Guarantor\Notifications\GuarantorDisputedNotification;
use Modules\Guarantor\Notifications\GuarantorDisputeResolvedNotification;
use Modules\Guarantor\Notifications\GuarantorEndedNotification;
use Modules\Guarantor\Notifications\GuarantorPaymentReceivedNotification;
use Modules\Guarantor\Notifications\GuarantorPendingReviewNotification;
use Modules\Guarantor\Notifications\GuarantorWithdrawnNotification;
use Modules\Guarantor\Notifications\InstallmentDueNotification;
use Modules\Guarantor\Notifications\InstallmentOverdueNotification;
use Modules\Guarantor\Notifications\InstallmentReleasedNotification;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Notifications\OpportunityCreatedConfirmationNotification;
use Modules\Opportunity\Notifications\OpportunityExpiredNotification;
use Modules\Opportunity\Notifications\OpportunityOfferAcceptedNotification;
use Modules\Opportunity\Notifications\OpportunityOfferRejectedNotification;
use Modules\Opportunity\Notifications\OpportunityOfferSubmittedNotification;
use Modules\Orders\Database\Factories\OrderOfferFactory;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\NewOrderAssignNotification;
use Modules\Orders\Notifications\OrderAcceptedOfferPriceDecreasedNotification;
use Modules\Orders\Notifications\OrderAcceptedOfferPriceIncreaseBlockedNotification;
use Modules\Orders\Notifications\OrderCancelledNotification;
use Modules\Orders\Notifications\OrderCreatedConfirmationNotification;
use Modules\Orders\Notifications\OrderEndedByProviderNotification;
use Modules\Orders\Notifications\OrderOfferAcceptedNotification;
use Modules\Orders\Notifications\OrderOfferCanceledNotification;
use Modules\Orders\Notifications\OrderOfferCreatedNotification;
use Modules\Orders\Notifications\OrderOfferRejectedNotification;
use Modules\Orders\Notifications\OrderPaymentCompletedNotification;
use Modules\Payment\Enums\PaymentStatusEnum;
use Tests\TestCase;

/**
 * Behavior-lock suite for DomainNotification migration.
 * Asserts exact via / toArray / toBroadcast / toFirebase / broadcastType / interfaces.
 */
function domainNotifiableUser(): User
{
    return User::factory()->create(['language' => 'en']);
}

function domainNotifiableProvider(): Provider
{
    return createWalletProvider(['language' => 'en']);
}

function createDomainOrderOffer(Order $order, array $offerAttrs = [])
{
    return OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider(createWalletProvider())
        ->create($offerAttrs);
}

function assertBroadcastPayload(BroadcastMessage $message, array $expectedData): void
{
    expect($message)->toBeInstanceOf(BroadcastMessage::class)
        ->and($message->data)->toBe($expectedData)
        ->and($message->connection)->toBe('sync');
}

function assertFirebaseMessage(FirebaseNotificationContent $message, string $title, string $body, array $data): void
{
    expect($message)->toBeInstanceOf(FirebaseNotificationContent::class)
        ->and($message->getTitle())->toBe($title)
        ->and($message->getBody())->toBe($body)
        ->and($message->getData())->toBe($data);
}

// ─── Orders ───────────────────────────────────────────────────────────────────

describe('Orders domain notification contracts', function (): void {
    it('locks NewOrderAssignNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $notification = new NewOrderAssignNotification($order);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldBroadcastNow::class)
            ->and($notification)->toBeInstanceOf(ShouldQueue::class)
            ->and($notification)->not->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('new assigned order')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'new_order_assigned',
                'body_translated_key' => 'you_have_been_assigned_a_new_order',
                'translated_attributes' => [],
                'order_id' => $order->id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('new_order_assigned', locale: 'en'),
            'body' => trans('you_have_been_assigned_a_new_order', locale: 'en'),
            'order_id' => $order->id,
        ]);
    });

    it('locks OrderOfferCreatedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $offer = createDomainOrderOffer($order);
        $notification = new OrderOfferCreatedNotification($offer);
        $user = domainNotifiableUser();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('order offer created')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'order_offer_created',
                'body_translated_key' => 'order_offer_has_been_created',
                'translated_attributes' => [],
                'order_id' => $offer->order_id,
                'offer_id' => $offer->id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('order_offer_created', locale: 'en'),
            'body' => trans('order_offer_has_been_created', locale: 'en'),
            'order_id' => $offer->order_id,
            'offer_id' => $offer->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('order_offer_created', locale: 'en'),
            trans('order_offer_has_been_created', locale: 'en'),
            [
                'order_id' => $offer->order_id,
                'offer_id' => $offer->id,
                'screen' => 'orders',
            ],
        );
    });

    it('locks OrderAcceptedOfferPriceDecreasedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $offer = createDomainOrderOffer($order, ['status' => OfferStatusEnum::Accepted]);
        $order->update(['accepted_offer_id' => $offer->id]);
        $order->refresh();
        $notification = new OrderAcceptedOfferPriceDecreasedNotification($order, 200.0, 175.0);
        $user = domainNotifiableUser();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->not->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('new assigned order')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'order_accepted_offer_price_decreased',
                'body_translated_key' => 'order_accepted_offer_price_decreased_body',
                'translated_attributes' => [
                    'old_price' => '200.00',
                    'new_price' => '175.00',
                ],
                'order_id' => $order->id,
                'offer_id' => $order->accepted_offer_id,
                'old_price' => '200.00',
                'new_price' => '175.00',
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('order_accepted_offer_price_decreased', locale: 'en'),
            'body' => trans('order_accepted_offer_price_decreased_body', ['old_price' => '200.00', 'new_price' => '175.00'], locale: 'en'),
            'order_id' => $order->id,
            'offer_id' => $order->accepted_offer_id,
            'old_price' => '200.00',
            'new_price' => '175.00',
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('order_accepted_offer_price_decreased', locale: 'en'),
            trans('order_accepted_offer_price_decreased_body', ['old_price' => '200.00', 'new_price' => '175.00'], locale: 'en'),
            [
                'order_id' => $order->id,
                'offer_id' => $order->accepted_offer_id,
                'old_price' => '200.00',
                'new_price' => '175.00',
                'screen' => 'orders',
            ],
        );
    });

    it('locks OrderAcceptedOfferPriceIncreaseBlockedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $offer = createDomainOrderOffer($order, ['status' => OfferStatusEnum::Cancelled]);
        $notification = new OrderAcceptedOfferPriceIncreaseBlockedNotification($order, $offer, 200.0, 300.0);
        $user = domainNotifiableUser();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('new assigned order')
            ->and($notification->toArray($user)['title_translated_key'])->toBe('order_accepted_offer_price_increase_blocked')
            ->and($notification->toArray($user)['old_price'])->toBe('200.00')
            ->and($notification->toArray($user)['attempted_new_price'])->toBe('300.00');
    });

    it('locks OrderOfferAcceptedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $offer = createDomainOrderOffer($order);
        $notification = new OrderOfferAcceptedNotification($offer);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('order offer accepted')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'order_offer_accepted',
                'body_translated_key' => 'order_offer_has_been_accepted',
                'translated_attributes' => [],
                'order_id' => $offer->order_id,
                'offer_id' => $offer->id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('order_offer_accepted', locale: 'en'),
            'body' => trans('order_offer_has_been_accepted', locale: 'en'),
            'order_id' => $offer->order_id,
            'offer_id' => $offer->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('order_offer_accepted', locale: 'en'),
            trans('order_offer_has_been_accepted', locale: 'en'),
            [
                'order_id' => $offer->order_id,
                'offer_id' => $offer->id,
                'screen' => 'orders',
            ],
        );
    });

    it('locks OrderOfferRejectedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $offer = createDomainOrderOffer($order);
        $notification = new OrderOfferRejectedNotification($offer);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('order offer rejected')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'order_offer_rejected',
                'body_translated_key' => 'order_offer_has_been_rejected',
                'translated_attributes' => [],
                'order_id' => $offer->order_id,
                'offer_id' => $offer->id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('order_offer_rejected', locale: 'en'),
            'body' => trans('order_offer_has_been_rejected', locale: 'en'),
            'order_id' => $offer->order_id,
            'offer_id' => $offer->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('order_offer_rejected', locale: 'en'),
            trans('order_offer_has_been_rejected', locale: 'en'),
            [
                'order_id' => $offer->order_id,
                'offer_id' => $offer->id,
                'screen' => 'orders',
            ],
        );
    });

    it('locks OrderOfferCanceledNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $offer = createDomainOrderOffer($order);
        $notification = new OrderOfferCanceledNotification($offer);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('order offer canceled')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'order_offer_canceled',
                'body_translated_key' => 'order_offer_has_been_canceled',
                'translated_attributes' => [],
                'order_id' => $offer->order_id,
                'offer_id' => $offer->id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('order_offer_canceled', locale: 'en'),
            'body' => trans('order_offer_has_been_canceled', locale: 'en'),
            'order_id' => $offer->order_id,
            'offer_id' => $offer->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('order_offer_canceled', locale: 'en'),
            trans('order_offer_has_been_canceled', locale: 'en'),
            [
                'order_id' => $offer->order_id,
                'offer_id' => $offer->id,
                'screen' => 'orders',
            ],
        );
    });

    it('locks OrderCancelledNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create([
            'status' => OrderStatusEnum::CancelledByClient,
            'cancellation_reason' => 'Provider did not start the work as agreed',
        ]);
        $notification = new OrderCancelledNotification($order);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('order cancelled')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'order_cancelled',
                'body_translated_key' => 'order_has_been_cancelled',
                'translated_attributes' => [],
                'order_id' => $order->id,
                'final_status' => $order->status->value,
                'cancellation_reason' => $order->cancellation_reason,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('order_cancelled', locale: 'en'),
            'body' => trans('order_has_been_cancelled', locale: 'en'),
            'order_id' => $order->id,
            'final_status' => $order->status->value,
            'cancellation_reason' => $order->cancellation_reason,
            'screen' => 'orders',
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('order_cancelled', locale: 'en'),
            trans('order_has_been_cancelled', locale: 'en'),
            [
                'order_id' => $order->id,
                'final_status' => $order->status->value,
                'cancellation_reason' => $order->cancellation_reason,
                'screen' => 'orders',
            ],
        );
    });

    it('locks OrderCreatedConfirmationNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $notification = new OrderCreatedConfirmationNotification($order);
        $user = domainNotifiableUser();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('order created confirmation')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'order_created',
                'body_translated_key' => 'order_has_been_created',
                'translated_attributes' => [],
                'order_id' => $order->id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('order_created', locale: 'en'),
            'body' => trans('order_has_been_created', locale: 'en'),
            'order_id' => $order->id,
            'screen' => 'orders',
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('order_created', locale: 'en'),
            trans('order_has_been_created', locale: 'en'),
            [
                'order_id' => $order->id,
                'screen' => 'orders',
            ],
        );
    });

    it('locks OrderEndedByProviderNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create(['status' => OrderStatusEnum::EndedByProvider]);
        $notification = new OrderEndedByProviderNotification($order);
        $user = domainNotifiableUser();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('order ended by provider')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'order_ended_by_provider',
                'body_translated_key' => 'order_has_been_ended_by_provider',
                'translated_attributes' => [],
                'order_id' => $order->id,
                'final_status' => $order->status->value,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('order_ended_by_provider', locale: 'en'),
            'body' => trans('order_has_been_ended_by_provider', locale: 'en'),
            'order_id' => $order->id,
            'final_status' => $order->status->value,
            'screen' => 'orders',
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('order_ended_by_provider', locale: 'en'),
            trans('order_has_been_ended_by_provider', locale: 'en'),
            [
                'order_id' => $order->id,
                'final_status' => $order->status->value,
                'screen' => 'orders',
            ],
        );
    });

    it('locks OrderPaymentCompletedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->inProgress()->create();
        $notification = new OrderPaymentCompletedNotification($order);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('order payment completed')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'order_payment_completed',
                'body_translated_key' => 'order_payment_has_been_completed',
                'translated_attributes' => [],
                'order_id' => $order->id,
                'final_status' => $order->status->value,
                'accepted_offer_id' => $order->accepted_offer_id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('order_payment_completed', locale: 'en'),
            'body' => trans('order_payment_has_been_completed', locale: 'en'),
            'order_id' => $order->id,
            'final_status' => $order->status->value,
            'screen' => 'orders',
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('order_payment_completed', locale: 'en'),
            trans('order_payment_has_been_completed', locale: 'en'),
            [
                'order_id' => $order->id,
                'final_status' => $order->status->value,
                'screen' => 'orders',
            ],
        );
    });
});

// ─── Guarantor ────────────────────────────────────────────────────────────────

describe('Guarantor domain notification contracts', function (): void {
    /**
     * @return array{0: GuarantorRequest, 1: User, 2: Provider}
     */
    function guarantorFixture(): array
    {
        $request = GuarantorRequest::factory()->create();
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        return [$request, $user, $provider];
    }

    it('locks GuarantorCreatedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $notification = new GuarantorCreatedNotification($request);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('guarantor created')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'guarantor_created',
                'body_translated_key' => 'guarantor_has_been_created',
                'translated_attributes' => [],
                'guarantor_request_id' => $request->id,
                'type' => $request->type->value,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('guarantor_created', locale: 'en'),
            'body' => trans('guarantor_has_been_created', locale: 'en'),
            'guarantor_request_id' => $request->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('guarantor_created', locale: 'en'),
            trans('guarantor_has_been_created', locale: 'en'),
            ['guarantor_request_id' => $request->id],
        );
    });

    it('locks GuarantorPaymentReceivedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $payment = createPaymentFor($user, $request, [
            'amount' => 1010,
            'driver' => 'testing',
            'status' => PaymentStatusEnum::Accepted,
        ]);
        $notification = new GuarantorPaymentReceivedNotification($request, $payment);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('guarantor payment received')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'guarantor_payment_received',
                'body_translated_key' => 'guarantor_payment_received_body',
                'translated_attributes' => [],
                'guarantor_request_id' => $request->id,
                'type' => $request->type->value,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('guarantor_payment_received', locale: 'en'),
            'body' => trans('guarantor_payment_received_body', locale: 'en'),
            'guarantor_request_id' => $request->id,
            'payment_id' => (string) $payment->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('guarantor_payment_received', locale: 'en'),
            trans('guarantor_payment_received_body', locale: 'en'),
            [
                'guarantor_request_id' => $request->id,
                'payment_id' => (string) $payment->id,
            ],
        );
    });

    it('locks GuarantorAcceptedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $notification = new GuarantorAcceptedNotification($request);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('guarantor accepted')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'guarantor_accepted',
                'body_translated_key' => 'guarantor_has_been_accepted',
                'translated_attributes' => [],
                'guarantor_request_id' => $request->id,
                'type' => $request->type->value,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('guarantor_accepted', locale: 'en'),
            'body' => trans('guarantor_has_been_accepted', locale: 'en'),
            'guarantor_request_id' => $request->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('guarantor_accepted', locale: 'en'),
            trans('guarantor_has_been_accepted', locale: 'en'),
            ['guarantor_request_id' => $request->id],
        );
    });

    it('locks GuarantorAdminApprovedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $notification = new GuarantorAdminApprovedNotification($request);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('guarantor admin approved')
            ->and($notification->toArray($user)['title_translated_key'])->toBe('guarantor_admin_approved')
            ->and($notification->toArray($user)['body_translated_key'])->toBe('guarantor_has_been_admin_approved')
            ->and($notification->toArray($user)['guarantor_request_id'])->toBe($request->id)
            ->and($notification->toArray($user)['type'])->toBe($request->type->value);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('guarantor_admin_approved', locale: 'en'),
            'body' => trans('guarantor_has_been_admin_approved', locale: 'en'),
            'guarantor_request_id' => $request->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('guarantor_admin_approved', locale: 'en'),
            trans('guarantor_has_been_admin_approved', locale: 'en'),
            ['guarantor_request_id' => $request->id],
        );
    });

    it('locks GuarantorAdminRejectedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $notification = new GuarantorAdminRejectedNotification($request);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('guarantor admin rejected')
            ->and($notification->toArray($user)['title_translated_key'])->toBe('guarantor_admin_rejected')
            ->and($notification->toArray($user)['body_translated_key'])->toBe('guarantor_has_been_admin_rejected');

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('guarantor_admin_rejected', locale: 'en'),
            'body' => trans('guarantor_has_been_admin_rejected', locale: 'en'),
            'guarantor_request_id' => $request->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('guarantor_admin_rejected', locale: 'en'),
            trans('guarantor_has_been_admin_rejected', locale: 'en'),
            ['guarantor_request_id' => $request->id],
        );
    });

    it('locks GuarantorCounterpartyRejectedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $notification = new GuarantorCounterpartyRejectedNotification($request);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('guarantor counterparty rejected')
            ->and($notification->toArray($user)['title_translated_key'])->toBe('guarantor_counterparty_rejected')
            ->and($notification->toArray($user)['body_translated_key'])->toBe('guarantor_has_been_counterparty_rejected');

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('guarantor_counterparty_rejected', locale: 'en'),
            'body' => trans('guarantor_has_been_counterparty_rejected', locale: 'en'),
            'guarantor_request_id' => $request->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('guarantor_counterparty_rejected', locale: 'en'),
            trans('guarantor_has_been_counterparty_rejected', locale: 'en'),
            ['guarantor_request_id' => $request->id],
        );
    });

    it('locks GuarantorEndedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $notification = new GuarantorEndedNotification($request);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('guarantor ended')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'guarantor_ended',
                'body_translated_key' => 'guarantor_has_been_ended',
                'translated_attributes' => [],
                'guarantor_request_id' => $request->id,
                'type' => $request->type->value,
                'final_status' => $request->status->value,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('guarantor_ended', locale: 'en'),
            'body' => trans('guarantor_has_been_ended', locale: 'en'),
            'guarantor_request_id' => $request->id,
            'final_status' => $request->status->value,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('guarantor_ended', locale: 'en'),
            trans('guarantor_has_been_ended', locale: 'en'),
            [
                'guarantor_request_id' => $request->id,
                'final_status' => $request->status->value,
            ],
        );
    });

    it('locks GuarantorCancelledNotification channel outputs', function (): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $request->cancellation_reason = 'Client withdrew from the contract';
        $notification = new GuarantorCancelledNotification($request);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('guarantor cancelled')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'guarantor_cancelled',
                'body_translated_key' => 'guarantor_has_been_cancelled',
                'translated_attributes' => [],
                'guarantor_request_id' => $request->id,
                'type' => $request->type->value,
                'cancellation_reason' => 'Client withdrew from the contract',
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('guarantor_cancelled', locale: 'en'),
            'body' => trans('guarantor_has_been_cancelled', locale: 'en'),
            'guarantor_request_id' => $request->id,
            'cancellation_reason' => 'Client withdrew from the contract',
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('guarantor_cancelled', locale: 'en'),
            trans('guarantor_has_been_cancelled', locale: 'en'),
            [
                'guarantor_request_id' => $request->id,
                'cancellation_reason' => 'Client withdrew from the contract',
            ],
        );
    });

    it('locks GuarantorDisputedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $request->status = GuarantorStatusEnum::Disputed;
        $reason = 'Work not delivered as agreed';
        $notification = new GuarantorDisputedNotification($request, $reason);
        $admin = Admin::query()->create([
            'name' => 'Dispute Admin',
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'language' => 'en',
        ]);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($admin))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('guarantor disputed')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'guarantor_disputed_title',
                'body_translated_key' => 'guarantor_disputed_body',
                'translated_attributes' => [],
                'guarantor_request_id' => $request->id,
                'type' => $request->type->value,
                'reason' => $reason,
                'final_status' => GuarantorStatusEnum::Disputed->value,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('guarantor_disputed_title', locale: 'en'),
            'body' => trans('guarantor_disputed_body', locale: 'en'),
            'guarantor_request_id' => $request->id,
            'final_status' => GuarantorStatusEnum::Disputed->value,
            'screen' => 'guarantor',
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('guarantor_disputed_title', locale: 'en'),
            trans('guarantor_disputed_body', locale: 'en'),
            [
                'guarantor_request_id' => $request->id,
                'final_status' => GuarantorStatusEnum::Disputed->value,
                'screen' => 'guarantor',
            ],
        );
    });

    it('locks GuarantorWithdrawnNotification channel outputs per audience', function (
        GuarantorWithdrawnNotificationAudience $audience,
        string $titleKey,
        string $bodyKey,
    ): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $request->status = GuarantorStatusEnum::Withdrawn;
        $reason = 'Changed plans';
        $notification = new GuarantorWithdrawnNotification($request, $audience, $reason);
        $admin = Admin::query()->create([
            'name' => 'Withdraw Admin',
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'language' => 'en',
        ]);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($admin))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('guarantor withdrawn')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => $titleKey,
                'body_translated_key' => $bodyKey,
                'translated_attributes' => [],
                'guarantor_request_id' => $request->id,
                'type' => $request->type->value,
                'reason' => $reason,
                'final_status' => GuarantorStatusEnum::Withdrawn->value,
                'audience' => $audience->value,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans($titleKey, locale: 'en'),
            'body' => trans($bodyKey, locale: 'en'),
            'guarantor_request_id' => $request->id,
            'final_status' => GuarantorStatusEnum::Withdrawn->value,
            'screen' => 'guarantor',
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans($titleKey, locale: 'en'),
            trans($bodyKey, locale: 'en'),
            [
                'guarantor_request_id' => $request->id,
                'final_status' => GuarantorStatusEnum::Withdrawn->value,
                'screen' => 'guarantor',
            ],
        );
    })->with([
        'withdrawer' => [
            GuarantorWithdrawnNotificationAudience::Withdrawer,
            'guarantor_withdrawn_withdrawer_title',
            'guarantor_withdrawn_withdrawer_body',
        ],
        'other party' => [
            GuarantorWithdrawnNotificationAudience::OtherParty,
            'guarantor_withdrawn_other_party_title',
            'guarantor_withdrawn_other_party_body',
        ],
        'admin' => [
            GuarantorWithdrawnNotificationAudience::Admin,
            'guarantor_withdrawn_admin_title',
            'guarantor_withdrawn_admin_body',
        ],
    ]);

    it('locks GuarantorDisputeResolvedNotification channel outputs for all resolution outcomes', function (
        GuarantorDisputeResolutionEnum $resolution,
        GuarantorStatusEnum $finalStatus,
        array $extraPayload,
        array $extraBroadcast,
        array $extraFirebase,
        array $translationReplacements,
    ): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $request->status = $finalStatus;
        $admin = Admin::query()->create([
            'name' => 'Resolve Admin',
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'language' => 'en',
        ]);

        $notification = new GuarantorDisputeResolvedNotification(
            $request,
            $resolution,
            requesterPercentage: $extraPayload['requester_percentage'] ?? null,
            counterpartyPercentage: $extraPayload['counterparty_percentage'] ?? null,
            requesterAmount: $extraPayload['requester_amount'] ?? null,
            counterpartyAmount: $extraPayload['counterparty_amount'] ?? null,
        );

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($admin))->toBe(['database', 'broadcast'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('guarantor dispute resolved')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => match ($resolution) {
                    GuarantorDisputeResolutionEnum::FullRequester => 'guarantor_dispute_resolved_full_requester_title',
                    GuarantorDisputeResolutionEnum::FullCounterparty => 'guarantor_dispute_resolved_full_counterparty_title',
                    GuarantorDisputeResolutionEnum::Escalate => 'guarantor_dispute_resolved_escalated_title',
                    GuarantorDisputeResolutionEnum::PercentageSplit => 'guarantor_dispute_resolved_percentage_split_title',
                },
                'body_translated_key' => match ($resolution) {
                    GuarantorDisputeResolutionEnum::FullRequester => 'guarantor_dispute_resolved_full_requester_body',
                    GuarantorDisputeResolutionEnum::FullCounterparty => 'guarantor_dispute_resolved_full_counterparty_body',
                    GuarantorDisputeResolutionEnum::Escalate => 'guarantor_dispute_resolved_escalated_body',
                    GuarantorDisputeResolutionEnum::PercentageSplit => 'guarantor_dispute_resolved_percentage_split_body',
                },
                'translated_attributes' => $translationReplacements,
                'guarantor_request_id' => $request->id,
                'type' => $request->type->value,
                'resolution' => $resolution->value,
                'final_status' => $finalStatus->value,
                ...$extraPayload,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans($notification->toArray($user)['title_translated_key'], $translationReplacements, locale: 'en'),
            'body' => trans($notification->toArray($user)['body_translated_key'], $translationReplacements, locale: 'en'),
            'guarantor_request_id' => $request->id,
            'resolution' => $resolution->value,
            'final_status' => $finalStatus->value,
            'screen' => 'guarantor',
            ...$extraBroadcast,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans($notification->toArray($user)['title_translated_key'], $translationReplacements, locale: 'en'),
            trans($notification->toArray($user)['body_translated_key'], $translationReplacements, locale: 'en'),
            [
                'guarantor_request_id' => $request->id,
                'resolution' => $resolution->value,
                'final_status' => $finalStatus->value,
                'screen' => 'guarantor',
                ...$extraFirebase,
            ],
        );
    })->with([
        'full_requester' => [
            GuarantorDisputeResolutionEnum::FullRequester,
            GuarantorStatusEnum::EndedViaDispute,
            [],
            [],
            [],
            [],
        ],
        'full_counterparty' => [
            GuarantorDisputeResolutionEnum::FullCounterparty,
            GuarantorStatusEnum::CancelledViaDispute,
            [],
            [],
            [],
            [],
        ],
        'escalate' => [
            GuarantorDisputeResolutionEnum::Escalate,
            GuarantorStatusEnum::Escalated,
            [],
            [],
            [],
            [],
        ],
        'percentage_split' => [
            GuarantorDisputeResolutionEnum::PercentageSplit,
            GuarantorStatusEnum::Settled,
            [
                'requester_percentage' => 60,
                'counterparty_percentage' => 40,
                'requester_amount' => 600.0,
                'counterparty_amount' => 404.0,
            ],
            [
                'requester_percentage' => '60',
                'counterparty_percentage' => '40',
                'requester_amount' => '600.00',
                'counterparty_amount' => '404.00',
            ],
            [
                'requester_percentage' => '60',
                'counterparty_percentage' => '40',
                'requester_amount' => '600.00',
                'counterparty_amount' => '404.00',
            ],
            [
                'requester_percentage' => 60,
                'counterparty_percentage' => 40,
                'requester_amount' => '600.00',
                'counterparty_amount' => '404.00',
            ],
        ],
    ]);

    it('locks GuarantorPendingReviewNotification channel outputs', function (): void {
        /** @var TestCase $this */
        [$request, $user] = guarantorFixture();
        $notification = new GuarantorPendingReviewNotification($request);
        $admin = Admin::query()->create([
            'name' => 'Guarantor Review Admin',
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'language' => 'en',
        ]);

        expect($notification->via($admin))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($user))->toBe(['database', 'broadcast'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('guarantor pending review')
            ->and($notification->toArray($admin))->toBe([
                'title_translated_key' => 'guarantor_pending_review_title',
                'body_translated_key' => 'guarantor_pending_review_body',
                'translated_attributes' => [],
                'guarantor_request_id' => $request->id,
                'type' => $request->type->value,
            ]);

        assertBroadcastPayload($notification->toBroadcast($admin), [
            'title' => trans('guarantor_pending_review_title', locale: 'en'),
            'body' => trans('guarantor_pending_review_body', locale: 'en'),
            'guarantor_request_id' => $request->id,
            'type' => $request->type->value,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($admin),
            trans('guarantor_pending_review_title', locale: 'en'),
            trans('guarantor_pending_review_body', locale: 'en'),
            [
                'guarantor_request_id' => $request->id,
                'screen' => 'guarantor',
            ],
        );
    });

    it('locks InstallmentDueNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $installment = GuarantorInstallment::factory()->create();
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();
        $notification = new InstallmentDueNotification($installment);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('installment due')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'installment_due',
                'body_translated_key' => 'installment_due_body',
                'translated_attributes' => [],
                'guarantor_request_id' => $installment->guarantor_request_id,
                'installment_id' => $installment->id,
                'installment_order' => $installment->order,
                'amount' => $installment->amount,
                'due_date' => $installment->due_date->toDateString(),
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('installment_due', locale: 'en'),
            'body' => trans('installment_due_body', locale: 'en'),
            'guarantor_request_id' => $installment->guarantor_request_id,
            'installment_id' => $installment->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('installment_due', locale: 'en'),
            trans('installment_due_body', locale: 'en'),
            [
                'guarantor_request_id' => $installment->guarantor_request_id,
                'installment_id' => $installment->id,
            ],
        );
    });

    it('locks InstallmentOverdueNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $installment = GuarantorInstallment::factory()->overdue()->create();
        $user = domainNotifiableUser();
        $notification = new InstallmentOverdueNotification($installment);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('installment overdue')
            ->and($notification->toArray($user)['title_translated_key'])->toBe('installment_overdue')
            ->and($notification->toArray($user)['body_translated_key'])->toBe('installment_overdue_body')
            ->and($notification->toArray($user)['installment_id'])->toBe($installment->id)
            ->and($notification->toArray($user)['amount'])->toBe($installment->amount);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('installment_overdue', locale: 'en'),
            'body' => trans('installment_overdue_body', locale: 'en'),
            'guarantor_request_id' => $installment->guarantor_request_id,
            'installment_id' => $installment->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('installment_overdue', locale: 'en'),
            trans('installment_overdue_body', locale: 'en'),
            [
                'guarantor_request_id' => $installment->guarantor_request_id,
                'installment_id' => $installment->id,
            ],
        );
    });

    it('locks InstallmentReleasedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $installment = GuarantorInstallment::factory()->create();
        $user = domainNotifiableUser();
        $notification = new InstallmentReleasedNotification($installment);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('installment released')
            ->and($notification->toArray($user)['title_translated_key'])->toBe('installment_released')
            ->and($notification->toArray($user)['body_translated_key'])->toBe('installment_released_body')
            ->and($notification->toArray($user)['installment_id'])->toBe($installment->id);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('installment_released', locale: 'en'),
            'body' => trans('installment_released_body', locale: 'en'),
            'guarantor_request_id' => $installment->guarantor_request_id,
            'installment_id' => $installment->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('installment_released', locale: 'en'),
            trans('installment_released_body', locale: 'en'),
            [
                'guarantor_request_id' => $installment->guarantor_request_id,
                'installment_id' => $installment->id,
            ],
        );
    });
});

// ─── Opportunity ──────────────────────────────────────────────────────────────

describe('Opportunity domain notification contracts', function (): void {
    it('locks OpportunityExpiredNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $opportunity = Opportunity::factory()->create();
        $notification = new OpportunityExpiredNotification($opportunity);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('opportunity expired')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'opportunity_expired',
                'body_translated_key' => 'opportunity_has_expired',
                'translated_attributes' => [],
                'opportunity_id' => $opportunity->id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('opportunity_expired', locale: 'en'),
            'body' => trans('opportunity_has_expired', locale: 'en'),
            'opportunity_id' => $opportunity->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('opportunity_expired', locale: 'en'),
            trans('opportunity_has_expired', locale: 'en'),
            [
                'opportunity_id' => $opportunity->id,
                'screen' => 'opportunity',
            ],
        );
    });

    it('locks OpportunityOfferSubmittedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $offer = OpportunityOffer::factory()->create();
        $notification = new OpportunityOfferSubmittedNotification($offer);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('opportunity offer submitted')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'opportunity_offer_submitted',
                'body_translated_key' => 'opportunity_offer_has_been_submitted',
                'translated_attributes' => [],
                'opportunity_id' => $offer->opportunity_id,
                'offer_id' => $offer->id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('opportunity_offer_submitted', locale: 'en'),
            'body' => trans('opportunity_offer_has_been_submitted', locale: 'en'),
            'opportunity_id' => $offer->opportunity_id,
            'offer_id' => $offer->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('opportunity_offer_submitted', locale: 'en'),
            trans('opportunity_offer_has_been_submitted', locale: 'en'),
            [
                'opportunity_id' => $offer->opportunity_id,
                'offer_id' => $offer->id,
                'screen' => 'opportunity',
            ],
        );
    });

    it('locks OpportunityOfferAcceptedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $offer = OpportunityOffer::factory()->create();
        $notification = new OpportunityOfferAcceptedNotification($offer);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('opportunity offer accepted')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'opportunity_offer_accepted',
                'body_translated_key' => 'opportunity_offer_has_been_accepted',
                'translated_attributes' => [],
                'opportunity_id' => $offer->opportunity_id,
                'offer_id' => $offer->id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('opportunity_offer_accepted', locale: 'en'),
            'body' => trans('opportunity_offer_has_been_accepted', locale: 'en'),
            'opportunity_id' => $offer->opportunity_id,
            'offer_id' => $offer->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('opportunity_offer_accepted', locale: 'en'),
            trans('opportunity_offer_has_been_accepted', locale: 'en'),
            [
                'opportunity_id' => $offer->opportunity_id,
                'offer_id' => $offer->id,
                'screen' => 'opportunity',
            ],
        );
    });

    it('locks OpportunityOfferRejectedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $offer = OpportunityOffer::factory()->create();
        $notification = new OpportunityOfferRejectedNotification($offer);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->broadcastType())->toBe('opportunity offer rejected')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'opportunity_offer_rejected',
                'body_translated_key' => 'opportunity_offer_has_been_rejected',
                'translated_attributes' => [],
                'opportunity_id' => $offer->opportunity_id,
                'offer_id' => $offer->id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('opportunity_offer_rejected', locale: 'en'),
            'body' => trans('opportunity_offer_has_been_rejected', locale: 'en'),
            'opportunity_id' => $offer->opportunity_id,
            'offer_id' => $offer->id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('opportunity_offer_rejected', locale: 'en'),
            trans('opportunity_offer_has_been_rejected', locale: 'en'),
            [
                'opportunity_id' => $offer->opportunity_id,
                'offer_id' => $offer->id,
                'screen' => 'opportunity',
            ],
        );
    });

    it('locks OpportunityCreatedConfirmationNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $opportunity = Opportunity::factory()->create();
        $notification = new OpportunityCreatedConfirmationNotification($opportunity);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('opportunity created confirmation')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'opportunity_created',
                'body_translated_key' => 'opportunity_has_been_created',
                'translated_attributes' => [],
                'opportunity_id' => $opportunity->id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('opportunity_created', locale: 'en'),
            'body' => trans('opportunity_has_been_created', locale: 'en'),
            'opportunity_id' => $opportunity->id,
            'screen' => 'opportunity',
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('opportunity_created', locale: 'en'),
            trans('opportunity_has_been_created', locale: 'en'),
            [
                'opportunity_id' => $opportunity->id,
                'screen' => 'opportunity',
            ],
        );
    });
});

it('keeps Chat NewMessageSentNotification outside DomainNotification', function (): void {
    $path = base_path('Modules/Chat/Infrastructure/Notifications/NewMessageSentNotification.php');
    $contents = file_get_contents($path);

    expect($contents)->not->toContain('DomainNotification')
        ->and(class_exists(NewMessageSentNotification::class))->toBeTrue();
});
