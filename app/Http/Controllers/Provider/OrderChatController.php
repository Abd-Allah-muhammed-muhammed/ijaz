<?php

namespace App\Http\Controllers\Provider;

use App\Enums\Order\OrderStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Provider;
use App\Models\User;
use App\Services\Chat\Facades\Chat;
use Modules\Chat\Http\Requests\SendMessageRequest;
use Modules\Chat\Http\Resources\ConversationCollection;
use Modules\Chat\Http\Resources\ConversationMessageCollection;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\ConversationResource;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use RuntimeException;
use Throwable;

class OrderChatController extends Controller
{
    use HasApiResponse;

    public function index(): JsonResponse
    {
        $user = auth()->user();
        $conversations = Conversation::latest('last_message_at')
            ->select('conversations.*')
            ->where('operation_type', Order::class)
            ->join('orders', function ($join) {
                $join
                    ->on('orders.id', 'conversations.operation_id')
                    ->where('orders.status', '!=', OrderStatusEnum::EndedByClient);
            })
            ->with(['lastMassage.sender', 'lastMassage.lastAttachment', 'user2', 'user1'])
            ->withCountUnreadMessagesFor($user)
            ->where(function (Builder $query) use ($user) {
                $query->whereMorphedTo('user1', $user)->orWhereMorphedTo('user2', $user);
            })
            ->paginate(15);

        return $this->successResponse(ConversationCollection::make($conversations));
    }

    public function store(Request $request): JsonResponse
    {
        $order_id = $request->input('order_id');
        $user = auth()->user();
        if (! $order_id) {
            return $this->failedResponse(
                errors: [
                    'order_id' => ['The order_id field is required.'],
                ],
                message: '',
                statusCode: 422
            );
        }
        $order = Order::find($order_id);
        if (! $order) {
            return $this->failedResponse(
                errors: [
                    'order_id' => ['The selected order_id is invalid.'],
                ],
                message: '',
                statusCode: 422
            );
        }
        if (! $order->user()->is($user) && ! $order->provider()->is($user)) {
            return $this->failedResponse(
                errors: [],
                message: 'not found',
                statusCode: 404
            );
        }
        $conversation = Chat::order($order)->getConversation();

        return $this->successResponse(
            ConversationResource::make($conversation->load(['lastMassage.sender', 'lastMassage.lastAttachment', 'user2', 'user1']))
        );

    }

    /**
     * @param  Conversation<Order>  $conversation
     *
     * @throws Throwable
     */
    public function send(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {

        DB::beginTransaction();
        try {
            $user = auth()->user();
            if (! $user) {
                DB::rollBack();

                return $this->failedResponse(
                    errors: [], message: 'User Not Authenticated', statusCode: 401
                );
            }
            if ($conversation->operation_type !== Order::class) {
                DB::rollBack();

                return $this->failedResponse(
                    errors: [], message: 'not found', statusCode: 404
                );

            }

            if (! $conversation->user1()->is($user) && ! $conversation->user2()->is($user)) {
                DB::rollBack();

                return $this->failedResponse(
                    errors: [], message: 'not found', statusCode: 404
                );
            }
            /**
             * @var Order $order
             */
            $order = $conversation->operation;
            $conversation = Chat::order($order);
            $conversation = match (get_class($user)) {
                User::class => $conversation->replayAsUser(
                    message: $request->input('content'),
                    attachments: $request->file('files', []),
                ),
                Provider::class => $conversation->replayAsProvider(
                    message: $request->input('content'),
                    attachments: $request->file('files', []),
                ),
                default => throw new RuntimeException('No Model Matched With '.get_class($user))
            };

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

    public function show(Conversation $conversation): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return $this->failedResponse(
                errors: [], message: 'User Not Authenticated', statusCode: 401
            );
        }
        if (! $conversation->user1()->is($user) && ! $conversation->user2()->is($user)) {
            return $this->failedResponse(
                errors: [], message: 'not found', statusCode: 404
            );
        }
        $conversation->messages()->whereNull('read_at')
            ->whereNotMorphedTo('sender', auth()->user())
            ->update(['read_at' => now()]);

        return $this->successResponse(ConversationMessageCollection::make(
            $conversation->messages()->latest()->with(['sender', 'attachments'])->paginate(15)
        ));
    }
}
