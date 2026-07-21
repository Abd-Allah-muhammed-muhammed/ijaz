<?php

namespace Modules\Support\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Http\Requests\SendSupportMessageRequest;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\Dashboard\ConversationMessageCollection;
use Modules\Chat\Services\ConversationService;
use Modules\Support\Contracts\Services\TicketSupportServiceInterface;
use Modules\Support\DTOs\StoreTicketSupportDTO;
use Modules\Support\Exceptions\TicketSupportNotDeletableException;
use Modules\Support\Http\Requests\TicketSupportRequest;
use Modules\Support\Http\Resources\Api\TicketSupportCollection;
use Modules\Support\Http\Resources\Api\TicketSupportResource;
use Modules\Support\Models\TicketSupport;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Tickets')]
class TicketSupportController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly TicketSupportServiceInterface $service,
        private readonly ConversationService $conversationService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            TicketSupportCollection::make(
                $this->service->indexForUser(
                    auth()->user(),
                    $request->integer('per_page', 10),
                )
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketSupportRequest $request): JsonResponse
    {
        try {
            $ticket = $this->service->store(
                StoreTicketSupportDTO::fromValidated($request->validated(), auth()->user()),
            );

            return $this->successResponse(TicketSupportResource::make($ticket));
        } catch (Throwable $e) {
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TicketSupport $ticketSupport): JsonResponse
    {
        $user = auth()->user();

        if (! $ticketSupport->user()->is($user)) {
            return $this->failedMessageResponse(trans('forbidden !!'), 403);
        }

        return $this->successResponse(TicketSupportResource::make($this->service->show($ticketSupport)));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TicketSupport $ticketSupport): JsonResponse
    {
        $user = auth()->user();

        if (! $ticketSupport->user()->is($user)) {
            return $this->failedMessageResponse(trans('forbidden !!'), 403);
        }

        try {
            $this->service->destroy($ticketSupport);

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (TicketSupportNotDeletableException $e) {
            return $this->failedMessageResponse(
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (Throwable $e) {
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function conversation(Request $request, TicketSupport $ticketSupport): JsonResponse
    {
        $user = auth()->user();

        if (! $ticketSupport->user()->is($user)) {
            return $this->failedMessageResponse(trans('forbidden !!'), 403);
        }

        $conversation = $ticketSupport->chat ?? $this->conversationService->ensureTicketSupportConversation($ticketSupport);

        return $this->successResponse(
            [
                'chat_id' => $conversation->id,
                'messages' => ConversationMessageCollection::make(
                    $this->conversationService->messages(
                        $conversation,
                        $user,
                        $request->integer('per_page', 15),
                    )
                ),
            ]
        );
    }

    public function conversationStore(SendSupportMessageRequest $request, TicketSupport $ticketSupport): JsonResponse
    {
        $user = auth()->user();

        if (! $ticketSupport->user()->is($user)) {
            return $this->failedMessageResponse(trans('forbidden !!'), 403);
        }

        $message = $this->conversationService->sendTicketSupportAsUser(
            $ticketSupport,
            ChatMessageData::fromRequest($request),
        );

        return $this->successResponse(ConversationMessageResource::make($message));
    }
}
