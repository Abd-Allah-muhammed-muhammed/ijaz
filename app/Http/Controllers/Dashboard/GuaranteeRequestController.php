<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\GuaranteeRequest\GuaranteeRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Chat\ConversationMessageCollection;
use App\Http\Resources\Dashboard\GuaranteeRequestCollection;
use App\Http\Resources\Dashboard\GuaranteeRequestResource;
use App\Models\GuaranteeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuaranteeRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $rows = GuaranteeRequest::query()
            ->with(['user', 'provider'])
            ->withCount(['media'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate($request->integer('perPage', 16));

        return inertia('Dashboard/GuaranteeRequests/Index', [
            'rows' => fn () => GuaranteeRequestCollection::make($rows),
            'prams' => function () use ($request) {
                return $request->all() ?: [];
            },
            'stats' => function () {
                $stats = [
                    'total' => GuaranteeRequest::count(),
                    'active' => GuaranteeRequest::whereIn('status', [GuaranteeRequestStatusEnum::InProgress, GuaranteeRequestStatusEnum::Approved])->count(),
                    'pending' => GuaranteeRequest::whereIn('status', [GuaranteeRequestStatusEnum::New])->count(),
                    'completed' => GuaranteeRequest::whereIn('status', [GuaranteeRequestStatusEnum::EndedByProvider, GuaranteeRequestStatusEnum::EndedByClient])->count(),
                    'cancelled' => GuaranteeRequest::whereIn('status', [GuaranteeRequestStatusEnum::CancelledByProvider, GuaranteeRequestStatusEnum::CancelledByClient, GuaranteeRequestStatusEnum::Refunded])->count(),
                ];

                return $stats;
            },
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(GuaranteeRequest $guaranteeRequest)
    {
        $guaranteeRequest->load([
            'media',
            'user',
            'provider',
        ]);

        return inertia('Dashboard/GuaranteeRequests/Show', [
            'guaranteeRequest' => GuaranteeRequestResource::make($guaranteeRequest),
        ]);
    }

    public function conversationMessages(Request $request, GuaranteeRequest $guaranteeRequest): JsonResponse
    {
        $chat = $guaranteeRequest->conversation;

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
