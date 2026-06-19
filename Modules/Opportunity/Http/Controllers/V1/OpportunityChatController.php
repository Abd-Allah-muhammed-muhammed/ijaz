<?php

namespace Modules\Opportunity\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\Chat\Resources\ConversationMessageCollection;
use App\Services\Chat\Resources\ConversationMessageResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Opportunity\DTOs\ChatData;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Http\Controllers\Concerns\AuthorizesOpportunityRequests;
use Modules\Opportunity\Http\Requests\SendOpportunityChatMessageRequest;
use Modules\Opportunity\Http\Requests\StoreChatRequest;
use Modules\Opportunity\Http\Resources\OpportunityConversationCollection;
use Modules\Opportunity\Http\Resources\OpportunityConversationResource;
use Modules\Opportunity\Services\OpportunityChatService;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

#[Group('Opportunity Chat')]
class OpportunityChatController extends Controller
{
    use AuthorizesOpportunityRequests;
    use HasApiResponse;

    public function __construct(
        private readonly OpportunityChatService $chatService,
    ) {}

    /**
     * List opportunity chats for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            OpportunityConversationCollection::make(
                $this->chatService->listForActor(
                    auth()->user(),
                    $request->integer('per_page', 15),
                )
            )
        );
    }

    /**
     * Open or get conversation for an opportunity.
     *
     * @throws Throwable
     */
    public function store(StoreChatRequest $request): JsonResponse
    {
        try {
            $data = ChatData::fromRequest($request);
            $opportunity = $this->chatService->resolveOpportunity($data->opportunity_id);

            $this->authorizeOrFail('chat', $opportunity, 'opportunity.chat_unauthorized');

            $conversation = $this->chatService->open($opportunity, auth()->user());

            return $this->successResponse(
                OpportunityConversationResource::make(
                    $conversation->load(['user1', 'user2', 'lastMassage', 'operation'])
                )
            );
        } catch (OpportunityException $e) {
            throw $e;
        } catch (Throwable $throwable) {
            report($throwable);

            if ($throwable instanceof HttpExceptionInterface) {
                return $this->failedMessageResponse($throwable->getMessage(), $throwable->getStatusCode());
            }

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * List messages in a conversation.
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeOrFail('view', $conversation, 'opportunity.chat_unauthorized');

        return $this->successResponse(
            ConversationMessageCollection::make(
                $this->chatService->listMessages(
                    $conversation,
                    auth()->user(),
                    $request->integer('per_page', 20),
                )
            )
        );
    }

    /**
     * Send a message in a conversation.
     *
     * @throws Throwable
     */
    public function send(SendOpportunityChatMessageRequest $request, Conversation $conversation): JsonResponse
    {
        try {
            $this->authorizeOrFail('send', $conversation, 'opportunity.chat_unauthorized');

            $message = $this->chatService->sendMessage($conversation, auth()->user(), $request);

            return $this->successResponse(ConversationMessageResource::make($message));
        } catch (OpportunityException $e) {
            throw $e;
        } catch (Throwable $throwable) {
            report($throwable);

            if ($throwable instanceof HttpExceptionInterface) {
                return $this->failedMessageResponse($throwable->getMessage(), $throwable->getStatusCode());
            }

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
