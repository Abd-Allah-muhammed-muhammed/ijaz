<?php

namespace Modules\Orders\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Orders\DTOs\CancelOrderDTO;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\DTOs\StoreOrderOfferDTO;
use Modules\Orders\DTOs\UpdateOrderOfferDTO;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Http\Requests\Provider\CancelOrderRequest;
use Modules\Orders\Http\Requests\Provider\OrderReviewRequest;
use Modules\Orders\Http\Requests\Provider\SubmitOfferRequest;
use Modules\Orders\Http\Resources\Dashboard\OfferCollection;
use Modules\Orders\Http\Resources\Dashboard\OrderCollection;
use Modules\Orders\Http\Resources\Dashboard\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Orders\Services\OrderOfferService;
use Modules\Orders\Services\OrderService;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderOfferService $orderOfferService,
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
        if ($request->filled('search')) {
            $filters['search'] = $request->search;
        }

        $rows = $this->orderService->listForProvider(
            auth('provider')->user(),
            $filters,
            $request->integer('perPage', 16),
        );

        return inertia('Provider/Orders/Index', [
            'rows' => OrderCollection::make($rows),
            'prams' => $request->all() ?: [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function new(Request $request)
    {
        $filters = [];
        if ($request->filled('period')) {
            $filters['period'] = $request->period;
        }
        if ($request->filled('date_from')) {
            $filters['date_from'] = $request->date_from;
        }
        if ($request->filled('search')) {
            $filters['search'] = $request->search;
        }

        $rows = $this->orderService->listRecommendedForProvider(
            auth('provider')->user(),
            $filters,
            $request->integer('perPage', 16),
        );

        return inertia('Provider/Orders/Recommended', [
            'rows' => OrderCollection::make($rows),
            'prams' => $request->all() ?: [],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order = $this->orderService->showForProvider($order, auth('provider')->user());

        return inertia('Provider/Orders/Show', [
            'order' => OrderResource::make($order),
        ]);
    }

    public function submitOffer(Order $order, SubmitOfferRequest $request): RedirectResponse
    {
        try {
            $this->orderOfferService->submit(
                $order,
                auth('provider')->user(),
                StoreOrderOfferDTO::fromValidated($request->validated()),
            );

            return redirect()->route('provider.orders.show', $order)->with('success', __('data saved successfully'));
        } catch (OrdersException $e) {
            return redirect()->back()->with('error', __($e->getTranslationKey()));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function updateOffer(Order $order, OrderOffer $offer, SubmitOfferRequest $request): RedirectResponse
    {
        try {
            $this->orderOfferService->update(
                $order,
                $offer,
                auth('provider')->user(),
                UpdateOrderOfferDTO::fromValidated($request->validated()),
            );

            return redirect()->route('provider.orders.show', $order)->with('success', __('data saved successfully'));
        } catch (OrdersException $e) {
            return redirect()->back()->with('error', __($e->getTranslationKey()));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function offers(Request $request)
    {
        $filters = [];
        if ($request->filled('status')) {
            $filters['status'] = $request->status;
        }
        if ($request->filled('search')) {
            $filters['search'] = $request->search;
        }

        $rows = $this->orderOfferService->listForProvider(
            auth('provider')->user(),
            $filters,
            $request->integer('perPage', 16),
        );

        return inertia('Provider/Orders/Offers', [
            'rows' => OfferCollection::make($rows),
            'prams' => $request->all() ?: [],
        ]);
    }

    /**
     * Delete an offer from an order.
     */
    public function deleteOffer(Order $order, OrderOffer $offer): RedirectResponse
    {
        try {
            $this->orderOfferService->deleteForProvider($order, $offer, auth('provider')->user());

            return redirect()->route('provider.orders.show', $order)->with('success', __('data deleted successfully'));
        } catch (OrdersException $e) {
            return redirect()->back()->with('error', __($e->getTranslationKey()));
        }
    }

    public function end(Order $order): RedirectResponse
    {
        try {
            $this->orderService->endForProvider($order, auth('provider')->user());

            return redirect()->back()->with('success', __('data updated successfully'));
        } catch (OrdersException $e) {
            return redirect()->back()->with('error', __($e->getTranslationKey()));
        }
    }

    public function cancel(Order $order, CancelOrderRequest $request): RedirectResponse
    {
        try {
            $this->orderService->cancel(
                $order,
                auth('provider')->user(),
                CancelOrderDTO::fromValidated($request->validated()),
            );

            return redirect()->back()->with('success', __('data updated successfully'));
        } catch (OrdersException $e) {
            return redirect()->back()->with('error', __($e->getTranslationKey()));
        }
    }

    public function updateReview(Order $order, OrderReviewRequest $request): RedirectResponse
    {
        try {
            $this->orderService->updateReviewForProvider(
                $order,
                auth('provider')->user(),
                EndAndReviewDTO::fromValidated($request->validated()),
            );

            return redirect()->back()->with('success', __('data updated successfully'));
        } catch (OrdersException $e) {
            return redirect()->back()->with('error', __($e->getTranslationKey()));
        }
    }
}
