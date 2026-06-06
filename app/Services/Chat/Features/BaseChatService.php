<?php

namespace App\Services\Chat\Features;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\System;
use App\Services\Chat\Contracts\HasConversation;
use App\Services\Chat\Events\ChatUpdatedEvent;
use App\Services\Chat\Events\NewMessageEvent;
use App\Services\Chat\Exceptions\ChatException;
use App\Services\Chat\Exceptions\ChatMessageException;
use App\Services\Chat\Jobs\NotifyChatMessageReceiver;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Pusher\ApiErrorException;

abstract class BaseChatService
{
    protected string $_attachment_storage = 'public';

    protected ?Conversation $chat = null;

    /**
     * getting file system disk currently storing files
     */
    public function getAttachmentStorage(): string
    {
        return $this->_attachment_storage;
    }

    /**
     * setting file system disk for storing files
     *
     * @return $this
     */
    public function setAttachmentStorage(string $attachment_storage): static
    {
        $this->_attachment_storage = $attachment_storage;

        return $this;
    }

    /**
     * setting chat object to send messages
     *
     * @return $this
     */
    public function setChat(?Conversation $chat): static
    {
        $this->chat = $chat;

        return $this;
    }

    /**
     * entry point to send message with files to chat object
     *
     * @throws ApiErrorException
     */
    protected function send(HasConversation $sender, ?string $message = null, array $attachments = []): Conversation
    {
        $onlineUsers = $this->getOnlineUsers();
        $receiver = $this->getReviver($sender);
        $read_at = $this->getReadAt($sender, $receiver, $onlineUsers);

        $lastMessage = DB::transaction(function () use ($message, $sender, $receiver, $read_at, $attachments) {
            $lastMessage = $this->generateMessage($message, $sender, $receiver, $read_at, collect($attachments));
            $this->attachLastMessage($lastMessage);

            if (empty($lastMessage->read_at)) {
                $this->notifyReceiver($lastMessage, $sender, $receiver);
            }

            return $lastMessage;
        });

        try {
            $this->triggerEvents($lastMessage, $sender, $receiver);
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->chat;
    }

    /**
     * get all users connected to presence chat channel
     *
     * @throws ApiErrorException
     */
    public function getOnlineUsers(): Collection
    {
        try {
            return collect(app(BroadcastManager::class)->getPusher()->get_users_info("presence-chats.{$this->chat->id}")
                ->users)->pluck('id');
        } catch (ApiErrorException $e) {
            if ($e->getCode() != 404) {
                throw $e;
            }

            return collect();
        }
    }

    /**
     * resolve reviver object form chat
     */
    abstract protected function getReviver(HasConversation $sender): HasConversation;

    protected function getReadAt(HasConversation $sender, HasConversation $receiver, Collection $onlineUsers): ?Carbon
    {
        if ($receiver instanceof System && $onlineUsers->contains(fn ($id) => str_contains($id, 'admin-'))) {
            return now();
        } elseif ($onlineUsers->contains($receiver->getAuthIdentifierForBroadcasting())) {
            return now();
        } else {
            return null;
        }
    }

    /**
     * create message model and files and attach message and files to chat object
     */
    protected function generateMessage(?string $message, HasConversation $sender, HasConversation $receiver, ?Carbon $read_at, Collection $attachments): ConversationMessage
    {
        $lastMessage = $this->chat
            ->messages()
            ->create([
                'content' => $message,
                'sender_id' => $sender->getKey(),
                'sender_type' => get_class($sender),
                'read_at' => $read_at,
                'receiver_id' => $receiver->getKey(),
                'receiver_type' => get_class($receiver),
                'has_attachments' => $attachments->isNotEmpty(),
            ])
            ->setRelation('sender', $sender)
            ->setRelation('receiver', $receiver);

        if ($attachments->isNotEmpty()) {
            //      $attachments->reduce(function ( ?FileAdder $fileAdder,UploadedFile $file)  use ($lastMessage){
            //        if($fileAdder === null){
            //          return $lastMessage->addMedia($file)->;
            //        }
            //        return $fileAdder->addMedia($file);
            //        return ;
            //      });
            //      $lastMessage->
            $files = $attachments->map(function (UploadedFile $file) use ($lastMessage) {
                return [
                    'id' => Str::uuid(),
                    'conversation_message_id' => $lastMessage->id,
                    'path' => $file->store('chat/attachment', $this->_attachment_storage),
                    'filename' => $file->getClientOriginalName(),
                    'type' => strtolower(explode('/', $file->getMimeType())[0]),
                    'store' => $this->_attachment_storage,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });
            $lastMessage->attachments()->insert($files->toArray());
            $lastMessage->load('attachments');
        }

        return $lastMessage;
    }

    public function attachLastMessage(ConversationMessage $lastMessage): void
    {
        $this->chat->update([
            'last_message_at' => $lastMessage->created_at,
            'last_message_id' => $lastMessage->id,
        ]);
        $this->chat->setRelation('lastMassage', $lastMessage);
    }

    /**
     * dispatch job to send notification to user
     */
    protected function notifyReceiver(ConversationMessage $message, HasConversation $sender, HasConversation $receiver): void
    {
        $route = '/chatRoom';
        if ($this->chat->user1_type === System::class || $this->chat->user2_type === System::class) {
            $route = '/supportChatRoom';
        }
        NotifyChatMessageReceiver::dispatch($message, $sender, $receiver, $route);
    }

    /**
     * trigger events associated to chat
     */
    protected function triggerEvents(ConversationMessage $lastMessage, HasConversation $sender, HasConversation $receiver): void
    {
        broadcast(new NewMessageEvent($lastMessage))->toOthers();
        broadcast(new ChatUpdatedEvent($this->chat, $sender, $receiver));
    }

    /**
     * validate before trying sending message
     *
     * @throws ChatMessageException
     * @throws ChatException
     */
    protected function validate(?string $message = null, array $attachments = [], array $extra = []): void
    {
        $attachmentsCollection = collect($attachments);
        $this->validateMessage($message, $attachmentsCollection, $extra);
        $this->validateChat($message, $attachmentsCollection, $extra);
    }

    /**
     * validate message content and files
     *
     * @throws ChatMessageException
     */
    protected function validateMessage(?string $message = null, ?Collection $attachments = null, array $extra = []): void
    {
        if (empty($message) && ($attachments?->isEmpty())) {
            throw ChatMessageException::nullableMessage();
        }
    }

    /**
     * validate chat
     *
     * @throws ChatException
     */
    protected function validateChat(?string $message = null, ?Collection $attachments = null, array $extra = []): void
    {
        if (empty($this->chat)) {
            throw ChatException::nullable();
        }
    }
}
