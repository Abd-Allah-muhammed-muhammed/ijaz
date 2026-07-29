<?php

namespace App\Notifications;

use App\Services\Firebase\DTO\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Shared shape for Orders / Guarantor / Opportunity domain notifications.
 *
 * Chat's NewMessageSentNotification is intentionally excluded — different product payload.
 *
 * Note: toBroadcast() must emit translated title/body strings (not toArray()'s translation
 * keys). broadcastData() defaults to payload() but Guarantor overrides when broadcast is a
 * subset of the database payload. firebaseData() is always abstract so Firebase `data`
 * cannot silently widen to match toArray/toBroadcast.
 */
abstract class DomainNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract protected function titleKey(): string;

    abstract protected function bodyKey(): string;

    /**
     * Extra entity fields stored on the database notification (after translation keys).
     *
     * @return array<string, mixed>
     */
    abstract protected function payload(): array;

    /**
     * Exact Firebase Message `data` bag — often a subset of payload()/broadcastData().
     *
     * @return array<string, mixed>
     */
    abstract protected function firebaseData(object $notifiable): array;

    abstract public function broadcastType(): string;

    protected function sendsFirebase(object $notifiable): bool
    {
        return false;
    }

    /**
     * Entity fields on the broadcast payload (after title/body).
     * Defaults to payload(); override when broadcast omits database-only fields.
     *
     * @return array<string, mixed>
     */
    protected function broadcastData(object $notifiable): array
    {
        return $this->payload();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($this->sendsFirebase($notifiable)) {
            $channels[] = 'firebase';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title_translated_key' => $this->titleKey(),
            'body_translated_key' => $this->bodyKey(),
            'translated_attributes' => [],
            ...$this->payload(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'title' => trans($this->titleKey(), locale: $notifiable->language),
            'body' => trans($this->bodyKey(), locale: $notifiable->language),
            ...$this->broadcastData($notifiable),
        ]))->onConnection('sync');
    }

    public function toFirebase(object $notifiable): Message
    {
        return Message::make(
            title: trans($this->titleKey(), locale: $notifiable->language),
            body: trans($this->bodyKey(), locale: $notifiable->language),
            data: $this->firebaseData($notifiable),
        );
    }
}
