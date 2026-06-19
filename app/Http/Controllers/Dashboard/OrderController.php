<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Http\Controllers\Controller;
use Modules\Chat\Http\Resources\Dashboard\ConversationMessageCollection;
use App\Http\Resources\Dashboard\OrderCollection;
use App\Http\Resources\Dashboard\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $rows = Order::query()
            ->with(['user', 'provider', 'city.translation', 'region.translation', 'category.translation'])
            ->withCount(['offers', 'media'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate($request->integer('perPage', 16));

        return inertia('Dashboard/Orders/Index', [
            'rows' => fn () => OrderCollection::make($rows),
            'prams' => function () use ($request) {
                return $request->all() ?: [];
            },
            'stats' => function () {
                return [
                    'total' => Order::count(),
                    'active' => Order::whereIn('status', [OrderStatusEnum::PaymentCompleted, OrderStatusEnum::InProgress])->count(),
                    'pending' => Order::whereIn('status', [OrderStatusEnum::New, OrderStatusEnum::Hold, OrderStatusEnum::OfferProvided])->count(),
                    'completed' => Order::whereIn('status', [OrderStatusEnum::EndedByProvider, OrderStatusEnum::EndedByClient])->count(),
                    'cancelled' => Order::whereIn('status', [OrderStatusEnum::CancelledByProvider, OrderStatusEnum::CancelledByClient, OrderStatusEnum::Refunded])->count(),
                ];
            },
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load([
            'category.translation',
            'media',
            'offers' => fn ($q) => $q->with(['provider'])
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [OfferStatusEnum::Accepted->value])
                ->orderByDesc('created_at'),
            'user',
            'provider' => function ($q) {
                $q->withAvg('reviews', 'rating');
            },
            'skills.translation',
            'city.translation',
            'region.translation',
            'reviews',
            // 'acceptedOffer.provider',
        ]);
        $order->loadCount([
            'offers',
            'media',
        ]);

        return inertia('Dashboard/Orders/Show', [
            'order' => OrderResource::make($order),
        ]);
    }

    public function conversationMessages(Request $request, Order $order): JsonResponse
    {
        $chat = $order->conversation;

        return response()->json([
            'success' => true,
            'data' => $chat ? ConversationMessageCollection::make(
                $chat->messages()
                    ->latest()
                    ->with(['sender', 'attachments'])
                    ->paginate(15)
                    ->withQueryString()
            ) : null,
        ]);
    }
}
