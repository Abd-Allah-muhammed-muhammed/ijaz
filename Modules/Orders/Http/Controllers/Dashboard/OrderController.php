<?php

namespace Modules\Orders\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Http\Requests\ListConversationMessagesRequest;
use Modules\Chat\Http\Requests\SendSupportMessageRequest;
use Modules\Chat\Http\Resources\ConversationMessageCollection;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Orders\Http\Requests\Dashboard\ResolveOrderDisputeRequest;
use Modules\Orders\Http\Resources\Dashboard\OrderCollection;
use Modules\Orders\Http\Resources\Dashboard\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\OrderService;

class OrderController extends Controller implements HasMiddleware
{
    use HasApiResponse;

    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show orders', only: ['index', 'show', 'conversationMessages']),
            new Middleware('permission:edit orders', only: ['sendConversationMessage', 'conversationTyping']),
            new Middleware('permission:manage orders', only: ['resolveDispute']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [];
        if ($request->filled('status')) {
            $filters['status'] = $request->status;
        }
        if ($request->filled('date_from')) {
            $filters['date_from'] = $request->date_from;
        }
        if ($request->filled('date_to')) {
            $filters['date_to'] = $request->date_to;
        }
        if ($request->filled('search')) {
            $filters['search'] = $request->search;
        }

        $rows = $this->orderService->listForDashboard($filters, $request->integer('perPage', 16));

        return inertia('Dashboard/Orders/Index', [
            'rows' => fn () => OrderCollection::make($rows),
            'prams' => function () use ($request) {
                return $request->all() ?: [];
            },
            'stats' => function () {
                return $this->orderService->dashboardStats();
            },
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order = $this->orderService->showForDashboard($order);

        return inertia('Dashboard/Orders/Show', [
            'order' => OrderResource::make($order),
        ]);
    }

    public function conversationMessages(ListConversationMessagesRequest $request, Order $order): JsonResponse
    {
        $messages = $this->orderService->conversationMessages(
            $order,
            $request->integer('per_page', 15),
            $request->searchTerm(),
        );

        if ($messages === null) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return $this->successResponse(
            ConversationMessageCollection::make($messages),
        );
    }

    public function sendConversationMessage(SendSupportMessageRequest $request, Order $order): JsonResponse
    {
        $message = $this->orderService->sendConversationMessageAsAdmin(
            $order,
            auth('admin')->user(),
            ChatMessageData::fromRequest($request),
        );

        return $this->successResponse(
            ConversationMessageResource::make($message)
        );
    }

    public function conversationTyping(Order $order): JsonResponse
    {
        $this->orderService->typingAsAdmin($order, auth('admin')->user());

        return $this->successResponse([]);
    }

    public function resolveDispute(ResolveOrderDisputeRequest $request, Order $order): RedirectResponse
    {
        $this->orderService->resolveDispute($order, $request, auth('admin')->user());

        return back()->with('success', __('data saved successfully'));
    }
}
