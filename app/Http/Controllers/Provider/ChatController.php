<?php

namespace App\Http\Controllers\Provider;

use App\Enums\Order\OrderStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Chat\ConversationCollection;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Provider;
use App\Models\User;
use App\Services\Chat\Contracts\HasConversation;
use App\Services\Chat\Facades\Chat;
use App\Services\Chat\Requests\SendMessageRequest;
use App\Services\Chat\Requests\StoreConversationRequest;
use App\Services\Chat\Resources\ConversationMessageResource;
use App\Services\Chat\Resources\ConversationResource;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Throwable;

class ChatController extends Controller
{
    use HasApiResponse;

    /**
     * Display the chat page.
     */
    public function index(Request $request): Response
    {
        /**
         * @var Provider $provider
         */
        $provider = auth('provider')->user();
        $rows = Conversation::query()
            ->select('conversations.*')
            ->where('operation_type', Order::class)
            ->join('orders', function ($join) {
                $join
                    ->on('orders.id', 'conversations.operation_id')
                    ->where('orders.status', '!=', OrderStatusEnum::EndedByClient);
            })
            ->with(['lastMassage.sender', 'lastMassage.lastAttachment', 'user2', 'user1'])
            ->withCountUnreadMessagesFor($provider)
            ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($provider) {
                $query->whereMorphedTo('user1', $provider)->orWhereMorphedTo('user2', $provider);
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Provider/Chat/Index', [
            'prams' => $request->all() ?: [],
            'rows' => ConversationCollection::make($rows),
            'current_conversation' => $request->filled('conversation') ? $rows->firstWhere('id', $request->get('conversation')) : null,
        ]);
    }

    /**
     * Display the chat conversation.
     *
     * @return AnonymousResourceCollection
     */
    public function show(Conversation $conversation, Request $request)
    {
        $conversation->messages()
            ->whereMorphedTo('receiver', auth('provider')->user())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        $messages = $conversation->messages()
            ->with(['sender', 'attachments'])
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return ConversationMessageResource::collection(collect($messages->items())->reverse()->values()->all());
    }

    /**
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function send(SendMessageRequest $request, Conversation $conversation)
    {
        DB::beginTransaction();
        try {

            $conversation = Chat::members($conversation)
                ->setSender(auth('provider')->user())
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

    public function store(StoreConversationRequest $request)
    {
        [$type, $id] = explode('-', $request->input('socket_id'));
        $user2 = $this->getModel($type, $id);
        if (is_null($user2) || $user2 == 'missing') {
            return $this->failedMessageResponse(trans('User Not Found'));
        }

        $user1 = auth()->user();
        $conv = $this->createNewChat($user1, $user2);

        //    return redirect()->action([self::class, 'index'], ['conversation' => $conv->id]);
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
        $chat = Conversation::where(function (\Illuminate\Database\Eloquent\Builder $query) use ($user1, $user2) {
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
}
