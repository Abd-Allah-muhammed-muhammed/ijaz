<?php

namespace Modules\Guarantor\Notifications;

use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Support\GuarantorFirebaseNotifiable;

class InstallmentReleasedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use GuarantorFirebaseNotifiable;

    public function __construct(public GuarantorInstallment $installment) {}

    protected function titleKey(): string
    {
        return 'installment_released';
    }

    protected function bodyKey(): string
    {
        return 'installment_released_body';
    }

    protected function payload(): array
    {
        return [
            'guarantor_request_id' => $this->installment->guarantor_request_id,
            'installment_id' => $this->installment->id,
            'installment_order' => $this->installment->order,
            'amount' => $this->installment->amount,
            'released_at' => $this->installment->released_at?->toIso8601String(),
        ];
    }

    protected function broadcastData(object $notifiable): array
    {
        return $this->firebaseData($notifiable);
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'guarantor_request_id' => $this->installment->guarantor_request_id,
            'installment_id' => $this->installment->id,
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $this->guarantorPartyReceivesFirebase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'installment released';
    }
}
