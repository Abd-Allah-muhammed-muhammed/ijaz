<?php

namespace Modules\Guarantor\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\Chat\Resources\ConversationMessageCollection;
use App\Services\Chat\Resources\ConversationMessageResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Guarantor\Http\Requests\SendMessageRequest;
use Modules\Guarantor\Http\Requests\StoreChatRequest;
use Modules\Guarantor\Http\Resources\Api\GuarantorConversationCollection;
use Modules\Guarantor\Http\Resources\Api\GuarantorConversationResource;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Services\GuarantorChatService;

#[Group('Guarantor Chat')]
class GuarantorChatController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly GuarantorChatService $chatService,
    ) {}

    /**
     * List guarantor chats for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            GuarantorConversationCollection::make(
                $this->chatService->listForActor(
                    auth()->user(),
                    $request->integer('per_page', 15)
                )
            )
        );
    }

    /**
     * Open or get conversation for a guarantor request.
     */
    public function store(StoreChatRequest $request): JsonResponse
    {
        $guarantorRequest = GuarantorRequest::findOrFail(
            $request->validated('guarantor_request_id')
        );

        $this->authorize('chat', $guarantorRequest);

        $conversation = $this->chatService->open($guarantorRequest);

        return $this->successResponse(
            GuarantorConversationResource::make(
                $conversation->load(['user1', 'user2', 'lastMassage', 'operation'])
            )
        );
    }

    /**
     * Show messages in a conversation.
     */
    public function show(
        Request $request,
        Conversation $conversation,
    ): JsonResponse {
        $this->authorize('view', $conversation);

        return $this->successResponse(
            ConversationMessageCollection::make(
                $this->chatService->listMessages(
                    $conversation,
                    $request->integer('per_page', 20)
                )
            )
        );
    }

    /**
     * Send a message in a conversation.
     */
    public function send(
        SendMessageRequest $request,
        Conversation $conversation,
    ): JsonResponse {
        $this->authorize('send', $conversation);

        $message = $this->chatService->send(
            $conversation,
            auth()->user(),
            $request
        );

        return $this->successResponse(
            ConversationMessageResource::make($message)
        );
    }
}
