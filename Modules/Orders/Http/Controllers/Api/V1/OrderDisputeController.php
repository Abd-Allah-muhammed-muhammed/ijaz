<?php

namespace Modules\Orders\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Orders\Http\Requests\Api\OpenOrderDisputeRequest;
use Modules\Orders\Http\Resources\Api\V1\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\OrderService;

class OrderDisputeController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function store(OpenOrderDisputeRequest $request, Order $order): JsonResponse
    {
        $this->authorize('dispute', $order);

        $actor = auth()->user();
        $actorRole = $actor instanceof Provider ? 'provider' : 'user';

        $updated = $this->orderService->openDispute(
            $order,
            $actor,
            $actorRole,
            (string) $request->validated('reason'),
        );

        return $this->successResponse(
            OrderResource::make($updated->load(['user', 'provider', 'acceptedOffer', 'histories']))
        );
    }
}
