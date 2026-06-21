<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Events\User\NewOrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Api\User\EndAndReviewRequest;
use App\Http\Requests\Api\Api\User\UpdateOfferStatusRequest;
use App\Http\Requests\Api\V1\OrderRequest;
use App\Http\Resources\Api\V1\OrderCollection;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\Provider;
use App\Models\Review;
use App\Models\User;
use App\Notifications\Provider\NewOrderAssignNotification;
use App\Notifications\Provider\OrderOfferAcceptedNotification;
use App\Notifications\Provider\OrderOfferCanceledNotification;
use App\Notifications\Provider\OrderOfferRejectedNotification;
use Dedoc\Scramble\Attributes\Group;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Payment\Services\PaymentService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Orders')]
class OrderController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            OrderCollection::make(
                auth()
                    ->user()
                    ->orders()
                    ->withCount(['offers'])
                    ->with(['category.translation'])
                    ->latest()
                    ->paginate($request->integer('per_page', 10))
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws ModelNotFoundException
     * @throws Throwable
     * @throws Exception
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(OrderRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $order = auth()->user()->orders()->create($data);
            if ($request->hasFile('files')) {
                $order->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection();
                });
            }
            //      $order->skills()->attach($data['skills']);
            if (empty($order->provider_id)) {
                $order->load('category.translation');
                NewOrderCreated::dispatch($order);
            } else {
                $order->provider->notify(new NewOrderAssignNotification($order));
            }
            $order->load(['media', 'skills.translation', 'city.translation', 'region.translation', 'category.translation']);
            DB::commit();

            return $this->successResponse(OrderResource::make($order));
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     * @throws Exception
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws ModelNotFoundException
     */
    public function edit(OrderRequest $request, Order $order): JsonResponse
    {
        if ($order->user()->isNot(auth()->user())) {
            return $this->failedMessageResponse(trans('forbidden !!'), Response::HTTP_FORBIDDEN);
        }
        if ($order->status->isNot(OrderStatusEnum::New)) {
            return $this->failedMessageResponse(trans('forbidden !!'), Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $data = $request->validated();
            $order->update($data);
            if ($request->hasFile('files')) {
                $order->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection();
                });
            }
            //      $order->skills()->sync($data['skills']);
            $order->load(['media', 'skills.translation', 'city.translation', 'region.translation', 'category.translation']);
            DB::commit();

            return $this->successResponse(OrderResource::make($order));
        } catch (Exception $e) {
            DB::rollBack();
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Remove the specified media from storage.
     *
     * @throws Throwable
     */
    public function deleteMedia(Order $order, Media $media): JsonResponse
    {
        if ($order->user()->isNot(auth()->user())) {
            return $this->failedMessageResponse(trans('forbidden !!'), Response::HTTP_FORBIDDEN);
        }
        if ($media->model()->isNot($order)) {
            return $this->failedMessageResponse(trans('forbidden !!'), Response::HTTP_FORBIDDEN);
        }
        if ($order->status->isNot(OrderStatusEnum::New)) {
            return $this->failedMessageResponse(trans('forbidden !!'), Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $media->delete();
            DB::commit();

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @throws ModelNotFoundException
     * @throws Throwable
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws HttpResponseException
     */
    public function show(Order $order): JsonResponse
    {
        $order->load([
            'offers.provider',
            'category.translation',
            'provider',
            'media',
            'skills.translation',
            'city.translation',
            'region.translation',
        ]);

        return $this->successResponse(OrderResource::make($order));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Throwable
     * @throws Exception
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws HttpResponseException
     */
    public function destroy(Order $order): JsonResponse
    {
        if ($order->offers()->exists()) {
            return $this->failedMessageResponse(__('you can not delete this order because it has offers'));
        }
        DB::beginTransaction();
        try {
            $order->delete();
            DB::commit();

            return $this->successMessageResponse(__('data deleted successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Update the specified offer in storage.
     *
     * @throws Throwable
     */
    public function updateOfferStatus(Order $order, OrderOffer $offer, UpdateOfferStatusRequest $request): JsonResponse
    {
        if ($offer->status->isIn([OfferStatusEnum::Cancelled, OfferStatusEnum::Rejected, OfferStatusEnum::Paid]) || $offer->order()->isNot($order)) {
            return $this->failedMessageResponse(__('you can not update this offer'));
        }
        DB::beginTransaction();
        try {
            $offer->update([
                'status' => $request->enum('status', OfferStatusEnum::class),
            ]);
            switch ($offer->status) {
                case OfferStatusEnum::Accepted:
                    if ($order->status->is(OrderStatusEnum::New)) {
                        $categoryFees = $order->category->getFees($offer->price);
                        $paymentGatewayFees = app('settings')->get($this->paymentService->getDefaultDriver().'_fees');
                        $fees = (float) $paymentGatewayFees + $categoryFees + (15 / 100 * $categoryFees);
                        $order->update([
                            'provider_id' => $offer->provider_id,
                            'accepted_offer_id' => $offer->id,
                            'status' => OrderStatusEnum::OfferProvided,
                            'price' => $offer->price,
                            'user_fees' => 0,
                            'provider_fees' => $fees,
                        ]);
                        $offer->provider->notify(new OrderOfferAcceptedNotification($offer));
                    }
                    break;
                case OfferStatusEnum::Rejected:
                    $offer->provider->notify(new OrderOfferRejectedNotification($offer));
                    break;
                case OfferStatusEnum::Pending:
                case OfferStatusEnum::Paid:
                    assert(false, 'unreachable');
                case OfferStatusEnum::Cancelled:
                    if ($offer->status->isNot(OfferStatusEnum::Cancelled)) {
                        $order->update([
                            'provider_id' => null,
                            'accepted_offer_id' => null,
                            'status' => OrderStatusEnum::New,
                            'price' => null,
                        ]);
                        $offer->provider->notify(new OrderOfferCanceledNotification($offer));
                    }
                    break;
            }
            DB::commit();

            return $this->successMessageResponse(__('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * Pay for the specified offer.
     *
     * @throws Throwable
     */
    public function pay(Order $order, OrderOffer $offer): JsonResponse
    {
        $user = auth()->user();

        if (
            $offer->status->isIn([OfferStatusEnum::Cancelled, OfferStatusEnum::Rejected, OfferStatusEnum::Paid]) ||
            $offer->order()->isNot($order) ||
            $order->user()->isNot($user)
        ) {
            return $this->failedMessageResponse(__('you can not pay for this order'));
        }

        try {
            $result = $this->paymentService->initiate(
                owner: $user,
                product: $offer,
                amount: $order->user_total,
            );

            if (! $result->isSuccessful()) {
                return $this->failedMessageResponse($result->message);
            }

            return $this->successResponse($result->toArray());
        } catch (Throwable $throwable) {

            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    /**
     * @throws Throwable
     */
    public function endAndReview(EndAndReviewRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();

        if ($order->user()->isNot(auth()->user())) {
            abort(404);
        }
        if ($order->status->isNotIn([OrderStatusEnum::InProgress, OrderStatusEnum::EndedByProvider])) {
            return $this->failedMessageResponse(__('you can not end this order'));
        }
        DB::beginTransaction();
        try {
            $order->update(['status' => OrderStatusEnum::EndedByClient]);
            Review::updateOrCreate([
                'reviewer_type' => User::class,
                'reviewer_id' => auth()->user()->id,
                'operation_type' => Order::class,
                'operation_id' => $order->id,
            ], [
                'reviewee_type' => Provider::class,
                'reviewee_id' => $order->provider_id,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
            ]);
            DB::commit();

            return $this->successMessageResponse(__('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }
}
