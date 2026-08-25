<?php

namespace Modules\Guarantor\Notifications;

use App\Models\User;
use App\Notifications\DomainNotification;
use App\Services\Firebase\DTO\FirebaseNotificationContent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;
use Modules\Guarantor\Models\GuarantorRequest;

/**
 * Admin resolved a dispute — DomainNotification with outcome-specific copy.
 */
class GuarantorDisputeResolvedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(
        public GuarantorRequest $guarantorRequest,
        public GuarantorDisputeResolutionEnum $resolution,
        public ?int $requesterPercentage = null,
        public ?int $counterpartyPercentage = null,
        public ?float $requesterAmount = null,
        public ?float $counterpartyAmount = null,
    ) {}

    protected function titleKey(): string
    {
        return match ($this->resolution) {
            GuarantorDisputeResolutionEnum::FullRequester => 'guarantor_dispute_resolved_full_requester_title',
            GuarantorDisputeResolutionEnum::FullCounterparty => 'guarantor_dispute_resolved_full_counterparty_title',
            GuarantorDisputeResolutionEnum::Escalate => 'guarantor_dispute_resolved_escalated_title',
            GuarantorDisputeResolutionEnum::PercentageSplit => 'guarantor_dispute_resolved_percentage_split_title',
        };
    }

    protected function bodyKey(): string
    {
        return match ($this->resolution) {
            GuarantorDisputeResolutionEnum::FullRequester => 'guarantor_dispute_resolved_full_requester_body',
            GuarantorDisputeResolutionEnum::FullCounterparty => 'guarantor_dispute_resolved_full_counterparty_body',
            GuarantorDisputeResolutionEnum::Escalate => 'guarantor_dispute_resolved_escalated_body',
            GuarantorDisputeResolutionEnum::PercentageSplit => 'guarantor_dispute_resolved_percentage_split_body',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function translationReplacements(): array
    {
        if ($this->resolution !== GuarantorDisputeResolutionEnum::PercentageSplit) {
            return [];
        }

        return [
            'requester_percentage' => $this->requesterPercentage ?? 0,
            'counterparty_percentage' => $this->counterpartyPercentage ?? 0,
            'requester_amount' => number_format((float) $this->requesterAmount, 2, '.', ''),
            'counterparty_amount' => number_format((float) $this->counterpartyAmount, 2, '.', ''),
        ];
    }

    protected function payload(): array
    {
        $payload = [
            'guarantor_request_id' => $this->guarantorRequest->id,
            'type' => $this->guarantorRequest->type->value,
            'resolution' => $this->resolution->value,
            'final_status' => $this->guarantorRequest->status->value,
        ];

        if ($this->resolution === GuarantorDisputeResolutionEnum::PercentageSplit) {
            $payload['requester_percentage'] = $this->requesterPercentage;
            $payload['counterparty_percentage'] = $this->counterpartyPercentage;
            $payload['requester_amount'] = $this->requesterAmount;
            $payload['counterparty_amount'] = $this->counterpartyAmount;
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
            'guarantor_request_id' => $this->guarantorRequest->id,
            'resolution' => $this->resolution->value,
            'final_status' => $this->guarantorRequest->status->value,
            'screen' => 'guarantor',
        ];

        if ($this->resolution === GuarantorDisputeResolutionEnum::PercentageSplit) {
            $data['requester_percentage'] = (string) ($this->requesterPercentage ?? 0);
            $data['counterparty_percentage'] = (string) ($this->counterpartyPercentage ?? 0);
            $data['requester_amount'] = number_format((float) $this->requesterAmount, 2, '.', '');
            $data['counterparty_amount'] = number_format((float) $this->counterpartyAmount, 2, '.', '');
        }

        return $data;
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User;
    }

    public function broadcastType(): string
    {
        return 'guarantor dispute resolved';
    }
}
