<?php

namespace Modules\Opportunity\Notifications;

use App\Models\User;
use App\Services\Firebase\DTO\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Opportunity\Models\Opportunity;

class OpportunityExpiredNotification extends Notification implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldQueue
{
    use Queueable;

    public function __construct(public Opportunity $opportunity) {}

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
            'title_translated_key' => 'opportunity_expired',
            'body_translated_key' => 'opportunity_has_expired',
            'translated_attributes' => [],
            'opportunity_id' => $this->opportunity->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'title' => trans('opportunity_expired', locale: $notifiable->language),
            'body' => trans('opportunity_has_expired', locale: $notifiable->language),
            'opportunity_id' => $this->opportunity->id,
        ]))->onConnection('sync');
    }

    public function broadcastType(): string
    {
        return 'opportunity expired';
    }

    public function toFirebase(object $notifiable): Message
    {
        return Message::make(
            title: trans('opportunity_expired', locale: $notifiable->language),
            body: trans('opportunity_has_expired', locale: $notifiable->language),
            data: ['opportunity_id' => $this->opportunity->id],
        );
    }
}
