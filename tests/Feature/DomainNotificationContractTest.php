<?php

use App\Models\Provider;
use App\Models\User;
use App\Services\Firebase\DTO\FirebaseNotificationContent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Chat\Infrastructure\Notifications\NewMessageSentNotification;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorAcceptedNotification;
use Modules\Guarantor\Notifications\GuarantorAdminApprovedNotification;
use Modules\Guarantor\Notifications\GuarantorAdminRejectedNotification;
use Modules\Guarantor\Notifications\GuarantorCounterpartyRejectedNotification;
use Modules\Guarantor\Notifications\GuarantorCreatedNotification;
use Modules\Guarantor\Notifications\GuarantorEndedNotification;
use Modules\Guarantor\Notifications\InstallmentDueNotification;
use Modules\Guarantor\Notifications\InstallmentOverdueNotification;
use Modules\Guarantor\Notifications\InstallmentReleasedNotification;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Notifications\OpportunityExpiredNotification;
use Modules\Opportunity\Notifications\OpportunityOfferAcceptedNotification;
use Modules\Opportunity\Notifications\OpportunityOfferRejectedNotification;
use Modules\Opportunity\Notifications\OpportunityOfferSubmittedNotification;
use Modules\Orders\Database\Factories\OrderOfferFactory;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\NewOrderAssignNotification;
use Modules\Orders\Notifications\OrderAcceptedOfferUpdatedNotification;
use Modules\Orders\Notifications\OrderOfferAcceptedNotification;
use Modules\Orders\Notifications\OrderOfferCanceledNotification;
use Modules\Orders\Notifications\OrderOfferCreatedNotification;
use Modules\Orders\Notifications\OrderOfferRejectedNotification;
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
            ->and($notification->via($provider))->toBe(['database', 'broadcast'])
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
            ['order_id' => $offer->order_id],
        );
    });

    it('locks OrderAcceptedOfferUpdatedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $offer = createDomainOrderOffer($order, ['status' => OfferStatusEnum::Accepted]);
        $order->update(['accepted_offer_id' => $offer->id]);
        $order->refresh();
        $notification = new OrderAcceptedOfferUpdatedNotification($order);
        $user = domainNotifiableUser();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification)->not->toBeInstanceOf(ShouldDispatchAfterCommit::class)
            ->and($notification->broadcastType())->toBe('new assigned order')
            ->and($notification->toArray($user))->toBe([
                'title_translated_key' => 'order_accepted_offer_updated',
                'body_translated_key' => 'the_order_accepted_offer_has_been_updated',
                'translated_attributes' => [],
                'order_id' => $order->id,
                'offer_id' => $order->accepted_offer_id,
            ]);

        assertBroadcastPayload($notification->toBroadcast($user), [
            'title' => trans('order_accepted_offer_updated', locale: 'en'),
            'body' => trans('the_order_accepted_offer_has_been_updated', locale: 'en'),
            'order_id' => $order->id,
            'offer_id' => $order->accepted_offer_id,
        ]);

        assertFirebaseMessage(
            $notification->toFirebase($user),
            trans('order_accepted_offer_updated', locale: 'en'),
            trans('the_order_accepted_offer_has_been_updated', locale: 'en'),
            ['order_id' => $order->id],
        );
    });

    it('locks OrderOfferAcceptedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $offer = createDomainOrderOffer($order);
        $notification = new OrderOfferAcceptedNotification($offer);
        $user = domainNotifiableUser();

        expect($notification->via($user))->toBe(['database', 'broadcast'])
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
    });

    it('locks OrderOfferRejectedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $offer = createDomainOrderOffer($order);
        $notification = new OrderOfferRejectedNotification($offer);
        $user = domainNotifiableUser();

        expect($notification->via($user))->toBe(['database', 'broadcast'])
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
    });

    it('locks OrderOfferCanceledNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $order = Order::factory()->create();
        $offer = createDomainOrderOffer($order);
        $notification = new OrderOfferCanceledNotification($offer);
        $user = domainNotifiableUser();

        expect($notification->via($user))->toBe(['database', 'broadcast'])
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
            ->and($notification->via($provider))->toBe(['database', 'broadcast'])
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

    it('locks GuarantorAcceptedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        [$request, $user, $provider] = guarantorFixture();
        $notification = new GuarantorAcceptedNotification($request);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast'])
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
            ->and($notification->via($provider))->toBe(['database', 'broadcast'])
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
            ->and($notification->via($provider))->toBe(['database', 'broadcast'])
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
            ->and($notification->via($provider))->toBe(['database', 'broadcast'])
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
            ->and($notification->via($provider))->toBe(['database', 'broadcast'])
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

    it('locks InstallmentDueNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $installment = GuarantorInstallment::factory()->create();
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();
        $notification = new InstallmentDueNotification($installment);

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast'])
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
            ->and($notification->via($provider))->toBe(['database', 'broadcast'])
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
            ['opportunity_id' => $opportunity->id],
        );
    });

    it('locks OpportunityOfferSubmittedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $offer = OpportunityOffer::factory()->create();
        $notification = new OpportunityOfferSubmittedNotification($offer);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast', 'firebase'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast'])
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
            ],
        );
    });

    it('locks OpportunityOfferAcceptedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $offer = OpportunityOffer::factory()->create();
        $notification = new OpportunityOfferAcceptedNotification($offer);
        $user = domainNotifiableUser();
        $provider = domainNotifiableProvider();

        expect($notification->via($user))->toBe(['database', 'broadcast'])
            ->and($notification->via($provider))->toBe(['database', 'broadcast'])
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
    });

    it('locks OpportunityOfferRejectedNotification channel outputs', function (): void {
        /** @var TestCase $this */
        $offer = OpportunityOffer::factory()->create();
        $notification = new OpportunityOfferRejectedNotification($offer);
        $user = domainNotifiableUser();

        expect($notification->via($user))->toBe(['database', 'broadcast'])
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
    });
});

it('keeps Chat NewMessageSentNotification outside DomainNotification', function (): void {
    $path = base_path('Modules/Chat/Infrastructure/Notifications/NewMessageSentNotification.php');
    $contents = file_get_contents($path);

    expect($contents)->not->toContain('DomainNotification')
        ->and(class_exists(NewMessageSentNotification::class))->toBeTrue();
});
