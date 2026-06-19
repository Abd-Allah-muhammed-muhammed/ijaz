<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SupportTickets\TicketSupportStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TicketSupportRequest;
use App\Http\Resources\Api\V1\TicketSupportCollection;
use App\Http\Resources\Api\V1\TicketSupportResource;
use App\Models\Order;
use App\Models\TicketSupport;
use DB;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Http\Requests\SendSupportMessageRequest;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Chat\Http\Resources\Dashboard\ConversationMessageCollection;
use Modules\Chat\Services\ConversationService;
use RuntimeException;
use Throwable;

#[Group('Tickets')]
class TicketSupportController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ConversationService $service,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        return $this->successResponse(
            TicketSupportCollection::make(
                TicketSupport::query()
                    ->whereMorphedTo('user', $user)
                    ->latest()
                    ->paginate($request->integer('per_page', 10))

            )
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(TicketSupportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $ticket = TicketSupport::create([
                'user_type' => get_class($user),
                'user_id' => $user->id,
                'operation_type' => match ($validated['operation_type'] ?? null) {
                    'order' => Order::class,
                    null => null,
                    default => throw new RuntimeException('invalid operation type'),
                },
                'operation_id' => $validated['operation_id'] ?? null,
                'title' => $validated['title'],
                'message' => $validated['message'],
                'status' => TicketSupportStatusEnum::Pending,
            ]);

            DB::commit();

            return $this->successResponse(TicketSupportResource::make($ticket));
        } catch (Throwable $e) {
            DB::rollBack();
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

        return $this->successResponse(TicketSupportResource::make($ticketSupport));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Throwable
     */
    public function destroy(TicketSupport $ticketSupport): JsonResponse
    {
        $user = auth()->user();

        if (! $ticketSupport->user()->is($user)) {
            return $this->failedMessageResponse(trans('forbidden !!'), 403);
        }

        if ($ticketSupport->status->isNot(TicketSupportStatusEnum::Pending)) {
            return $this->failedMessageResponse(__('you can not delete this ticket'));
        }

        DB::beginTransaction();
        try {
            $ticketSupport->delete();
            DB::commit();

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
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

        $conversation = $ticketSupport->chat ?? $this->service->ensureTicketSupportConversation($ticketSupport);

        return $this->successResponse(
            [
                'chat_id' => $conversation->id,
                'messages' => ConversationMessageCollection::make(
                    $this->service->messages(
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

        $message = $this->service->sendTicketSupportAsUser(
            $ticketSupport,
            ChatMessageData::fromRequest($request),
        );

        return $this->successResponse(ConversationMessageResource::make($message));
    }
}
