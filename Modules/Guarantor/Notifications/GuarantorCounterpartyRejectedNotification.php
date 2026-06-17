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
use Modules\Guarantor\Models\GuarantorRequest;

class GuarantorCounterpartyRejectedNotification extends Notification implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldQueue
{
    use Queueable;

    public function __construct(public GuarantorRequest $guarantorRequest) {}

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
            'title_translated_key' => 'guarantor_counterparty_rejected',
            'body_translated_key' => 'guarantor_has_been_counterparty_rejected',
            'translated_attributes' => [],
            'guarantor_request_id' => $this->guarantorRequest->id,
            'type' => $this->guarantorRequest->type->value,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'title' => trans('guarantor_counterparty_rejected', locale: $notifiable->language),
            'body' => trans('guarantor_has_been_counterparty_rejected', locale: $notifiable->language),
            'guarantor_request_id' => $this->guarantorRequest->id,
        ]))->onConnection('sync');
    }

    public function broadcastType(): string
    {
        return 'guarantor counterparty rejected';
    }

    public function toFirebase(object $notifiable): Message
    {
        return Message::make(
            title: trans('guarantor_counterparty_rejected', locale: $notifiable->language),
            body: trans('guarantor_has_been_counterparty_rejected', locale: $notifiable->language),
            data: ['guarantor_request_id' => $this->guarantorRequest->id],
        );
    }
}
