<?php

namespace Modules\Guarantor\Notifications;

use App\Models\User;
use App\Services\Firebase\DTO\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Guarantor\Models\GuarantorInstallment;

class InstallmentDueNotification extends Notification implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldQueue
{
    use Queueable;

    public function __construct(public GuarantorInstallment $installment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User
            ? ['database', 'broadcast', 'firebase']
            : ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title_translated_key' => 'installment_due',
            'body_translated_key' => 'installment_due_body',
            'translated_attributes' => [],
            'guarantor_request_id' => $this->installment->guarantor_request_id,
            'installment_id' => $this->installment->id,
            'installment_order' => $this->installment->order,
            'amount' => $this->installment->amount,
            'due_date' => $this->installment->due_date->toDateString(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'title' => trans('installment_due', locale: $notifiable->language),
            'body' => trans('installment_due_body', locale: $notifiable->language),
            'guarantor_request_id' => $this->installment->guarantor_request_id,
            'installment_id' => $this->installment->id,
        ]))->onConnection('sync');
    }

    public function broadcastType(): string
    {
        return 'installment due';
    }

    public function toFirebase(object $notifiable): Message
    {
        return Message::make(
            title: trans('installment_due', locale: $notifiable->language),
            body: trans('installment_due_body', locale: $notifiable->language),
            data: [
                'guarantor_request_id' => $this->installment->guarantor_request_id,
                'installment_id' => $this->installment->id,
            ],
        );
    }
}
