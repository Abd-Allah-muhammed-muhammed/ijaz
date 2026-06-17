<?php

namespace App\Services\Chat\Features;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\Provider;
use App\Services\Chat\Contracts\HasConversation;
use App\Services\Chat\Exceptions\ChatException;
use Illuminate\Http\UploadedFile;
use Pusher\ApiErrorException;

class OrderChat extends BaseChatService
{
    /**
     * @throws ChatException
     */

    /**
     * OrderChat constructor.
     *
     * @throws ChatException
     */
    public function __construct(protected Order $order)
    {
        if ($this->order->user === null || $this->order->provider === null) {
            throw ChatException::invalidOrderUsers();
        }
        $this->chat = $this->order->conversation ?? $this->generateChat();
    }

    public function getConversation()
    {
        return $this->chat;
    }

    /**
     * creating support chat for user
     */
    protected function generateChat(): Conversation
    {
        /**
         * @var HasConversation $user
         */
        $user = $this->order->user;
        /**
         * @var HasConversation $provider
         */
        $provider = $this->order->provider;

        return $this->order->conversation()->create([
            'user1_type' => get_class($user),
            'user1_id' => $user->getKey(),
            'user2_type' => get_class($provider),
            'user2_id' => $provider->getKey(),
        ]);

    }

    /**
     * send message as specific Provider
     *
     * @param  UploadedFile[]  $attachments
     *
     * @throws ApiErrorException
     */
    public function replayAsProvider(?string $message = null, array $attachments = []): Conversation
    {
        return $this->send($this->order->provider, $message, $attachments);
    }

    /**
     * replay as support chat user
     *
     * @param  UploadedFile[]  $attachments
     *
     * @throws ApiErrorException
     */
    public function replayAsUser(?string $message = null, array $attachments = []): Conversation
    {
        return $this->send($this->order->user, $message, $attachments);
    }

    /**
     * setting chat object to send messages
     *
     * @return $this
     *
     * @throws ChatException
     */
    public function setChat(?Conversation $chat): static
    {
        if ($chat === null) {
            throw ChatException::nullable();
        }
        if ($chat->operation_type !== Order::class) {
            throw ChatException::notOrderChat();
        }

        $this->chat = $chat;
        $this->order = $chat->operation;

        return $this;
    }

    /**
     * validate chat
     *
     * @throws ChatException
     */

    /**
     * resolve receiver object from chat
     */
    protected function getReceiver(HasConversation $sender): HasConversation
    {
        if ($sender instanceof Provider) {
            return $this->chat->user2;
        }

        return $this->chat->user1;
    }
}
