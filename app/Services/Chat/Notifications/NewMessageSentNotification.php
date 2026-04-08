<?php

namespace App\Services\Chat\Notifications;

use App\Services\Firebase\DTO\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageSentNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public ?string $content, public User $sender, public bool $hasAttachment, public string $chat_id, public string $route = '/chatRoom')
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['firebase'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    public function toFirebase(object $notifiable): Message
    {
        return Message::make($this->sender->name ?? trans('system', $notifiable->language), $this->content ?? '📑', [
            'sender_name' => $this->sender->name ?? '',
            'sender_image' => $this->sender->image_url ?? '',
            'has_attachments' => (string) $this->hasAttachment,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => (string) $this->route,
            'id' => $this->chat_id,
        ]);
    }
}
