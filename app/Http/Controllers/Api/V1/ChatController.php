<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Provider;
use App\Models\System;
use App\Models\User;
use App\Services\Chat\Contracts\HasConversation;
use App\Services\Chat\Facades\Chat;
use Modules\Chat\Http\Requests\SendMessageRequest;
use Modules\Chat\Http\Requests\SendSupportMessageRequest;
use Modules\Chat\Http\Requests\StoreConversationRequest;
use Modules\Chat\Http\Resources\ConversationCollection;
use Modules\Chat\Http\Resources\ConversationMessageCollection;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\ConversationResource;
use DB;
use Dedoc\Scramble\Attributes\Group;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use RuntimeException;
use Throwable;

#[Group('Chat')]
class ChatController extends Controller
{
    use HasApiResponse;

    public function index(): JsonResponse
    {
        $user = auth('provider')->user();
        $conversations = Conversation::latest('last_message_at')->with(['lastMassage.sender', 'lastMassage.lastAttachment', 'user2', 'user1'])
            ->withCountUnreadMessagesFor($user)
            ->where('operation_type', Order::class)
            ->where(function (Builder $query) use ($user) {
                $query->whereMorphedTo('user2', $user)->orWhereMorphedTo('user1', $user);
            })
            ->paginate(15);

        return $this->successResponse(ConversationCollection::make($conversations));
    }

    /**
     * @throws Throwable
     */
    public function sendToProvider(Request $request, Provider $provider): JsonResponse
    {
        DB::beginTransaction();
        try {

            $conversation = Chat::members(Conversation::find($request->chat_id))
                ->setSender(auth()->user())
                ->replay($request->input('content'), $request->file('files', []));

            DB::commit();

            return $this->successResponse(ConversationMessageResource::make(
                $conversation->lastMassage
            ));
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return $this->failedMessageResponse(trans('messages.general_error'));
        }
    }

    /**
     * @throws Throwable
     */
    public function sendToUser(Request $request, User $user): JsonResponse
    {
        DB::beginTransaction();
        try {
            $conversation = Chat::members(Conversation::find($request->chat_id))
                ->setSender(auth()->user())
                ->replay($request->input('content'), $request->file('files', []));
            DB::commit();

            return $this->successResponse(ConversationMessageResource::make(
                $conversation->lastMassage
            ));
        } catch (Exception $exception) {
            report($exception);
            DB::rollBack();

            return $this->failedMessageResponse(trans('messages.general_error'));
        }
    }

    /**
     * @throws Throwable
     */
    public function send(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        DB::beginTransaction();
        try {
            $conversation = Chat::members($conversation)
                ->setSender(auth()->user())
                ->replay($request->input('content'), $request->file('files', []));
            DB::commit();

            return $this->successResponse(ConversationMessageResource::make(
                $conversation->lastMassage
            ));
        } catch (Exception $exception) {
            report($exception);
            DB::rollBack();

            return $this->failedMessageResponse(trans('messages.general_error'));
        }
    }

    /**
     * @throws Throwable
     */
    public function sendToSupport(SendSupportMessageRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $chat = Chat::support($user)
                ->replayAsSupportable(
                    message: $request->input('content'),
                    attachments: $request->file('files', []),
                );
            DB::commit();

            return $this->successResponse(ConversationMessageResource::make($chat->lastMassage));
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);

            return $this->failedMessageResponse(trans('messages.general_error'));
        }
    }

    public function supportMessages(): JsonResponse
    {
        $user = auth()->user();
        $chat = $user->supportChat()->firstOrCreate([], [
            'user1_type' => System::class,
            'user1_id' => 1,
        ]);

        return $this->successResponse(
            [
                'chat_id' => $chat->id,
                'messages' => ConversationMessageCollection::make(
                    $chat->messages()
                        ->latest()
                        ->with(['sender', 'attachments'])
                        ->paginate(15)
                ),
            ]
        );
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $conversation->messages()->whereNull('read_at')
            ->whereNotMorphedTo('sender', auth()->user())
            ->update(['read_at' => now()]);

        return $this->successResponse(ConversationMessageCollection::make(
            $conversation->messages()->latest()->with(['sender', 'attachments'])->paginate(15)
        ));
    }

    public function chat(Conversation $conversation): JsonResponse
    {
        $conversation->load(['lastMassage.sender', 'lastMassage.lastAttachment', 'user2', 'user1'])
            ->loadCount([
                'messages as unread_count' => function (Builder $query) {
                    $query->whereNull('read_at')
                        ->where('sender_id', '!=', auth()->id())
                        ->orWhere('sender_type', '!=', get_class(auth()->user()));
                },
            ]);

        return $this->successResponse(ConversationResource::make($conversation));
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        [$type, $id] = explode('-', $request->input('socket_id'));
        $user2 = $this->getModel($type, $id);
        if (is_null($user2) || $user2 == 'missing') {
            return $this->failedMessageResponse(trans('User Not Found'));
        }

        $user1 = auth()->user();
        $conv = $this->createNewChat($user1, $user2);

        //    $conv = $this->createNewChat(Provider::find(2), Provider::find(3));
        return $this->successResponse(
            ConversationResource::make($conv->load(['lastMassage.sender', 'lastMassage.lastAttachment', 'user2', 'user1']))
        );
    }

    public function getModel(string $type, string $id): HasConversation|string|null
    {
        return match ($type) {
            'user' => User::find($id),
            'provider' => Provider::find($id),
            default => 'missing',
        };
    }

    private function createNewChat(HasConversation $user1, HasConversation $user2): Conversation
    {
        $chat = Conversation::where(function (Builder $query) use ($user1, $user2) {
            $query->where('user1_type', get_class($user1))->where('user1_id', $user1->getKey())
                ->where('user2_type', get_class($user2))->where('user2_id', $user2->getKey());
        })->Orwhere(function (Builder $query) use ($user2, $user1) {
            $query->where('user1_type', get_class($user2))->where('user1_id', $user2->getKey())
                ->where('user2_type', get_class($user1))->where('user2_id', $user1->getKey());
        })->first();
        if (! $chat) {
            return Conversation::create([
                'user1_type' => get_class($user1),
                'user1_id' => $user1->getKey(),
                'user2_type' => get_class($user2),
                'user2_id' => $user2->getKey(),
            ]);
        }

        return $chat;
    }

    /**
     * @return Provider|User
     */
    protected function mapModels(string $type)
    {
        return match ($type) {
            'user' => new User,
            'provider' => new Provider,
            default => throw new RuntimeException('No Mode Matched With '.$type)
        };
    }
}
