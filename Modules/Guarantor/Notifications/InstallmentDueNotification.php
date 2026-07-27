<?php

namespace Modules\Guarantor\Notifications;

use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Guarantor\Models\GuarantorInstallment;

class InstallmentDueNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public GuarantorInstallment $installment) {}

    protected function titleKey(): string
    {
        return 'installment_due';
    }

    protected function bodyKey(): string
    {
        return 'installment_due_body';
    }

    protected function payload(): array
    {
        return [
            'guarantor_request_id' => $this->installment->guarantor_request_id,
            'installment_id' => $this->installment->id,
            'installment_order' => $this->installment->order,
            'amount' => $this->installment->amount,
            'due_date' => $this->installment->due_date->toDateString(),
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
        return $notifiable instanceof User;
    }

    public function broadcastType(): string
    {
        return 'installment due';
    }
}
