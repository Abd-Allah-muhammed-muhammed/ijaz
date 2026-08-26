<?php

namespace Modules\Guarantor\Notifications;

use App\Models\Admin;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Guarantor\Models\GuarantorInstallment;

class UnpaidOverdueInstallmentEscalationNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public GuarantorInstallment $installment) {}

    protected function titleKey(): string
    {
        return 'unpaid_overdue_installment_escalation_title';
    }

    protected function bodyKey(): string
    {
        return 'unpaid_overdue_installment_escalation_body';
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

    protected function firebaseData(object $notifiable): array
    {
        return [
            'guarantor_request_id' => $this->installment->guarantor_request_id,
            'installment_id' => $this->installment->id,
            'screen' => 'guarantor',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof Admin;
    }

    public function broadcastType(): string
    {
        return 'unpaid overdue installment escalation';
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $replace = [
            'order' => $this->installment->order,
            'amount' => $this->installment->amount,
        ];

        return (new BroadcastMessage([
            'title' => trans($this->titleKey(), $replace, $notifiable->language),
            'body' => trans($this->bodyKey(), $replace, $notifiable->language),
            ...$this->broadcastData($notifiable),
        ]))->onConnection('sync');
    }
}
