<?php

namespace Modules\Guarantor\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Modules\Chat\Models\Conversation;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Http\Resources\ConversationMessageCollection;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Guarantor\Http\Requests\SendGuarantorMessageRequest;
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
     *
     * @authenticated
     *
     * @queryParam per_page int Results per page. Example: 15
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "items": [
     *       {
     *         "id": "01234567-89ab-cdef-0123-456789abcdef",
     *         "guarantor_request_id": "...",
     *         "last_message_at": "2026-06-01T10:00:00+00:00",
     *         "unread_count": 2
     *       }
     *     ],
     *     "total": 5,
     *     "per_page": 15,
     *     "current_page": 1
     *   }
     * }
     * @response 401 { "success": false, "message": "Unauthenticated." }
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
     *
     * Chat is only available when guarantor status is approved, in_progress, or overdue.
     *
     * @authenticated
     *
     * @bodyParam guarantor_request_id string required Guarantor request UUID.
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "id": "01234567-89ab-cdef-0123-456789abcdef",
     *     "guarantor_request_id": "...",
     *     "user1": { "id": "...", "name": "Ahmed" },
     *     "user2": { "id": "...", "name": "Ali" }
     *   }
     * }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 403 { "success": false, "message": "You are not authorized to perform this action" }
     * @response 422 { "success": false, "message": "Chat is only available after the request is approved" }
     */
    public function store(StoreChatRequest $request): JsonResponse
    {
        $guarantorRequest = GuarantorRequest::findOrFail(
            $request->validated('guarantor_request_id')
        );

        $this->authorize('chat', $guarantorRequest);

        $conversation = $this->chatService->open($guarantorRequest, auth()->user());

        return $this->successResponse(
            GuarantorConversationResource::make(
                $conversation->load(['user1', 'user2', 'lastMessage', 'operation'])
            )
        );
    }

    /**
     * Show messages in a guarantor conversation.
     *
     * @authenticated
     *
     * @urlParam conversation string required Conversation UUID.
     *
     * @queryParam per_page int Results per page. Example: 20
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "items": [
     *       { "id": "...", "content": "Hello", "created_at": "2026-06-01T10:00:00+00:00" }
     *     ],
     *     "total": 10,
     *     "per_page": 20,
     *     "current_page": 1
     *   }
     * }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 403 { "success": false, "message": "You are not authorized to perform this action" }
     * @response 404 { "success": false, "message": "Not found" }
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
                    auth()->user(),
                    $request->integer('per_page', 20)
                )
            )
        );
    }

    /**
     * Send a message in a guarantor conversation.
     *
     * @authenticated
     *
     * @urlParam conversation string required Conversation UUID.
     *
     * @bodyParam content string Message text (required if no files).
     * @bodyParam files file[] Optional file attachments.
     *
     * @response 200 {
     *   "status": true,
     *   "data": {
     *     "id": "...",
     *     "content": "Hello",
     *     "created_at": "2026-06-01T10:00:00+00:00"
     *   }
     * }
     * @response 401 { "success": false, "message": "Unauthenticated." }
     * @response 403 { "success": false, "message": "You are not authorized to perform this action" }
     * @response 422 { "success": false, "message": "Validation error" }
     */
    public function send(
        SendGuarantorMessageRequest $request,
        Conversation $conversation,
    ): JsonResponse {
        $this->authorize('send', $conversation);

        $message = $this->chatService->send(
            $conversation,
            auth()->user(),
            ChatMessageData::fromRequest($request),
        );

        return $this->successResponse(
            ConversationMessageResource::make($message)
        );
    }
}
