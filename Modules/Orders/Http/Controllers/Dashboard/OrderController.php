<?php

namespace Modules\Orders\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Chat\Http\Resources\Dashboard\ConversationMessageCollection;
use Modules\Orders\Http\Resources\Dashboard\OrderCollection;
use Modules\Orders\Http\Resources\Dashboard\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

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

    public function conversationMessages(Request $request, Order $order): JsonResponse
    {
        $messages = $this->orderService->conversationMessages($order, 15);

        return response()->json([
            'success' => true,
            'data' => $messages ? ConversationMessageCollection::make($messages) : null,
        ]);
    }
}
