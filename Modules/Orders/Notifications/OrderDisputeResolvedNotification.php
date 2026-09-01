<?php

namespace Modules\Orders\Notifications;

use App\Notifications\DomainNotification;
use App\Services\Firebase\DTO\FirebaseNotificationContent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Orders\Enums\OrderDisputeResolutionEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Support\OrderFirebaseNotifiable;

class OrderDisputeResolvedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use OrderFirebaseNotifiable;

    public function __construct(
        public Order $order,
        public OrderDisputeResolutionEnum $resolution,
        public ?int $userPercentage = null,
        public ?int $providerPercentage = null,
        public ?float $userAmount = null,
        public ?float $providerAmount = null,
    ) {}

    protected function titleKey(): string
    {
        return match ($this->resolution) {
            OrderDisputeResolutionEnum::FullUser => 'order_dispute_resolved_full_user_title',
            OrderDisputeResolutionEnum::FullProvider => 'order_dispute_resolved_full_provider_title',
            OrderDisputeResolutionEnum::Escalate => 'order_dispute_resolved_escalated_title',
            OrderDisputeResolutionEnum::PercentageSplit => 'order_dispute_resolved_percentage_split_title',
        };
    }

    protected function bodyKey(): string
    {
        return match ($this->resolution) {
            OrderDisputeResolutionEnum::FullUser => 'order_dispute_resolved_full_user_body',
            OrderDisputeResolutionEnum::FullProvider => 'order_dispute_resolved_full_provider_body',
            OrderDisputeResolutionEnum::Escalate => 'order_dispute_resolved_escalated_body',
            OrderDisputeResolutionEnum::PercentageSplit => 'order_dispute_resolved_percentage_split_body',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function translationReplacements(): array
    {
        if ($this->resolution !== OrderDisputeResolutionEnum::PercentageSplit) {
            return [];
        }

        return [
            'user_percentage' => $this->userPercentage ?? 0,
            'provider_percentage' => $this->providerPercentage ?? 0,
            'user_amount' => number_format((float) $this->userAmount, 2, '.', ''),
            'provider_amount' => number_format((float) $this->providerAmount, 2, '.', ''),
        ];
    }

    protected function payload(): array
    {
        $payload = [
            'order_id' => $this->order->id,
            'resolution' => $this->resolution->value,
            'final_status' => $this->order->status->value,
        ];

        if ($this->resolution === OrderDisputeResolutionEnum::PercentageSplit) {
            $payload['user_percentage'] = $this->userPercentage;
            $payload['provider_percentage'] = $this->providerPercentage;
            $payload['user_amount'] = $this->userAmount;
            $payload['provider_amount'] = $this->providerAmount;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title_translated_key' => $this->titleKey(),
            'body_translated_key' => $this->bodyKey(),
            'translated_attributes' => $this->translationReplacements(),
            ...$this->payload(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'title' => trans($this->titleKey(), $this->translationReplacements(), locale: $notifiable->language),
            'body' => trans($this->bodyKey(), $this->translationReplacements(), locale: $notifiable->language),
            ...$this->broadcastData($notifiable),
        ]))->onConnection('sync');
    }

    public function toFirebase(object $notifiable): FirebaseNotificationContent
    {
        return FirebaseNotificationContent::make(
            title: trans($this->titleKey(), $this->translationReplacements(), locale: $notifiable->language),
            body: trans($this->bodyKey(), $this->translationReplacements(), locale: $notifiable->language),
            data: $this->firebaseData($notifiable),
        );
    }

    protected function broadcastData(object $notifiable): array
    {
        return $this->firebaseData($notifiable);
    }

    protected function firebaseData(object $notifiable): array
    {
        $data = [
            'order_id' => $this->order->id,
            'resolution' => $this->resolution->value,
            'final_status' => $this->order->status->value,
            'screen' => 'orders',
        ];

        if ($this->resolution === OrderDisputeResolutionEnum::PercentageSplit) {
            $data['user_percentage'] = (string) ($this->userPercentage ?? 0);
            $data['provider_percentage'] = (string) ($this->providerPercentage ?? 0);
            $data['user_amount'] = number_format((float) $this->userAmount, 2, '.', '');
            $data['provider_amount'] = number_format((float) $this->providerAmount, 2, '.', '');
        }

        return $data;
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $this->orderPartyReceivesFirebase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'order dispute resolved';
    }
}
