<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SupportTickets\TicketSupportStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\TicketSupport;
use App\Services\Chat\Facades\Chat;
use App\Services\Chat\Requests\SendMessageRequest;
use App\Services\Chat\Resources\ConversationCollection;
use App\Services\Chat\Resources\ConversationMessageCollection;
use App\Services\Chat\Resources\ConversationMessageResource;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Throwable;

class TicketSupportChatController extends Controller
{
    use HasApiResponse;

    public function index(): JsonResponse
    {
        $user = auth()->user();
        $conversations = Conversation::latest('last_message_at')
            ->select('conversations.*')
            ->where('conversations.operation_type', TicketSupport::class)
            ->join('ticket_supports', function ($join) {
                $join
                    ->on('ticket_supports.id', 'conversations.operation_id')
                    ->where('ticket_supports.status', '!=', TicketSupportStatusEnum::Closed);
            })
            ->with(['lastMassage.sender', 'lastMassage.lastAttachment', 'user2', 'user1'])
            ->withCountUnreadMessagesFor($user)
            ->where(function (Builder $query) use ($user) {
                $query->whereMorphedTo('user1', $user)->orWhereMorphedTo('user2', $user);
            })
            ->paginate(15);

        return $this->successResponse(ConversationCollection::make($conversations));
    }

    /**
     * @param  Conversation<TicketSupport>  $conversation
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
            if ($conversation->operation_type !== TicketSupport::class) {
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
             * @var TicketSupport $order
             */
            $order = $conversation->operation;

            $conversation = Chat::support($order)
                ->replayAsSupportable(
                    message: $request->input('content'),
                    attachments: $request->file('files', []),
                );

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
