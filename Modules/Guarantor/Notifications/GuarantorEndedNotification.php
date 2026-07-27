<?php

namespace Modules\Guarantor\Notifications;

use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Guarantor\Models\GuarantorRequest;

class GuarantorEndedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public GuarantorRequest $guarantorRequest) {}

    protected function titleKey(): string
    {
        return 'guarantor_ended';
    }

    protected function bodyKey(): string
    {
        return 'guarantor_has_been_ended';
    }

    protected function payload(): array
    {
        return [
            'guarantor_request_id' => $this->guarantorRequest->id,
            'type' => $this->guarantorRequest->type->value,
            'final_status' => $this->guarantorRequest->status->value,
        ];
    }

    protected function broadcastData(object $notifiable): array
    {
        return $this->firebaseData($notifiable);
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'guarantor_request_id' => $this->guarantorRequest->id,
            'final_status' => $this->guarantorRequest->status->value,
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User;
    }

    public function broadcastType(): string
    {
        return 'guarantor ended';
    }
}
