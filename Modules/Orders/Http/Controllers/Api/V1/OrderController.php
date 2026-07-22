<?php

namespace Modules\Orders\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\DTOs\StoreOrderDTO;
use Modules\Orders\DTOs\UpdateOfferStatusDTO;
use Modules\Orders\DTOs\UpdateOrderDTO;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Http\Requests\Api\EndAndReviewRequest;
use Modules\Orders\Http\Requests\Api\OrderRequest;
use Modules\Orders\Http\Requests\Api\UpdateOfferStatusRequest;
use Modules\Orders\Http\Resources\Api\V1\OrderCollection;
use Modules\Orders\Http\Resources\Api\V1\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Orders\Services\OrderOfferService;
use Modules\Orders\Services\OrderService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

#[Group('Orders')]
class OrderController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderOfferService $orderOfferService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            OrderCollection::make(
                $this->orderService->listForUser(auth()->user(), $request->integer('per_page', 10))
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->create(
                auth()->user(),
                StoreOrderDTO::fromValidated(
                    $request->validated(),
                    $request->hasFile('files') ? $request->file('files') : null,
                ),
            );

            return $this->successResponse(OrderResource::make($order));
        } catch (Throwable $e) {
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function edit(OrderRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->update(
                $order,
                auth()->user(),
                UpdateOrderDTO::fromValidated(
                    $request->validated(),
                    $request->hasFile('files') ? $request->file('files') : null,
                ),
            );

            return $this->successResponse(OrderResource::make($order));
        } catch (OrdersException $e) {
            return $this->failedMessageResponse(__($e->getTranslationKey()), $e->getHttpStatusCode());
        } catch (Throwable $e) {
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Remove the specified media from storage.
     */
    public function deleteMedia(Order $order, Media $media): JsonResponse
    {
        try {
            $this->orderService->deleteMedia($order, $media, auth()->user());

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (OrdersException $e) {
            return $this->failedMessageResponse(__($e->getTranslationKey()), $e->getHttpStatusCode());
        } catch (Throwable $e) {
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order): JsonResponse
    {
        return $this->successResponse(
            OrderResource::make($this->orderService->showForUser($order, auth()->user()))
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order): JsonResponse
    {
        try {
            $this->orderService->delete($order, auth()->user());

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (OrdersException $e) {
            return $this->failedMessageResponse(__($e->getTranslationKey()), $e->getHttpStatusCode());
        } catch (HttpResponseException|HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Update the specified offer in storage.
     */
    public function updateOfferStatus(Order $order, OrderOffer $offer, UpdateOfferStatusRequest $request): JsonResponse
    {
        try {
            $this->orderOfferService->updateStatus(
                $order,
                $offer,
                auth()->user(),
                UpdateOfferStatusDTO::fromValidated($request->validated()),
            );

            return $this->successMessageResponse(__('data saved successfully'));
        } catch (OrdersException $e) {
            return $this->failedMessageResponse(__($e->getTranslationKey()), $e->getHttpStatusCode());
        } catch (HttpResponseException|HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Pay for the specified offer.
     */
    public function pay(Order $order, OrderOffer $offer): JsonResponse
    {
        try {
            $result = $this->orderOfferService->pay($order, $offer, auth()->user());

            if (! $result->isSuccessful()) {
                return $this->failedMessageResponse($result->message);
            }

            return $this->successResponse($result->toArray());
        } catch (OrdersException $e) {
            return $this->failedMessageResponse(__($e->getTranslationKey()), $e->getHttpStatusCode());
        } catch (Throwable $e) {
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function endAndReview(EndAndReviewRequest $request, Order $order): JsonResponse
    {
        try {
            $this->orderService->endAndReview(
                $order,
                auth()->user(),
                EndAndReviewDTO::fromValidated($request->validated()),
            );

            return $this->successMessageResponse(__('data saved successfully'));
        } catch (OrdersException $e) {
            return $this->failedMessageResponse(__($e->getTranslationKey()), $e->getHttpStatusCode());
        } catch (HttpResponseException|HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
