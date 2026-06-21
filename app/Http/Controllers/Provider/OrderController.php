<?php

namespace App\Http\Controllers\Provider;

use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\OrderReviewRequest;
use App\Http\Requests\Provider\SubmitOfferRequest;
use App\Http\Resources\Dashboard\OfferCollection;
use App\Http\Resources\Dashboard\OrderCollection;
use App\Http\Resources\Dashboard\OrderResource;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\Provider;
use App\Models\Review;
use App\Models\User;
use App\Notifications\User\OrderAcceptedOfferUpdatedNotification;
use App\Notifications\User\OrderOfferCreatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $rows = auth('provider')->user()->orders()
            ->with(['user'])
            ->withCount(['offers', 'media'])
            ->latest()
            ->paginate($request->integer('perPage', 16));

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
        $rows = Order::whereIntegerInRaw('category_id', auth('provider')->user()->providerCategories()->pluck('category_id'))
            ->where('status', OrderStatusEnum::New)
            ->with(['user'])
            ->latest()
            ->withCount(['offers', 'media'])
            ->whereNull('accepted_offer_id')
            ->paginate($request->integer('perPage', 16));

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
        $order->load([
            'category',
            'provider',
            'media',
            'offers' => function ($query) {
                $query->where('provider_id', auth('provider')->id());
            },
            'user',
            'skills.translation',
            'city.translation',
            'region.translation',
            'reviews',
        ]);
        $order->loadCount([
            'offers',
            'media',
        ]);

        return inertia('Provider/Orders/Show', [
            'order' => OrderResource::make($order),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function submitOffer(Order $order, SubmitOfferRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $offer = $order->offers()->create([
                'provider_id' => auth('provider')->id(),
                'price' => $request->float('price'),
                'description' => $request->string('description'),
                'status' => 'pending',
                'user_id' => $order->user_id,
                'category_id' => $order->category_id,
            ]);

            $order->user->notify(new OrderOfferCreatedNotification($offer));
            DB::commit();

            // Notify the user about the new offer
            // $order->user->notify(new \App\Notifications\OrderOfferSubmitted($order));
            return redirect()->route('provider.orders.show', $order)->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }

    }

    public function updateOffer(Order $order, OrderOffer $offer, SubmitOfferRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            if ($offer->provider()->isNot(auth('provider')->user())) {
                abort(404);
            }
            if ($offer->status->isNotIn([OfferStatusEnum::Pending, OfferStatusEnum::Accepted])) {
                return redirect()->back()->with('error', __('you can not edit this offer because it has been processed.'));
            }

            $offer->fill([
                'price' => $request->float('price'),
                'description' => $request->string('description'),
            ]);

            if (! $offer->isDirty()) {
                return redirect()->route('provider.orders.show', $order)->with('success', __('data saved successfully'));
            }

            $offer->update();
            if ($offer->status->is(OfferStatusEnum::Accepted) && $order->acceptedOffer()->is($offer)) {
                $categoryFees = $order->category->getFees($offer->price);
                $paymentGatewayFees = app('settings')->get(config('payment.default').'_fees');
                $providerFees = floatval($paymentGatewayFees) + $categoryFees + (15 / 100 * $categoryFees);
                $order->update([
                    'price' => $offer->price,
                    'user_fees' => 0,
                    'provider_fees' => $providerFees,
                ]);
                $order->user->notify(new OrderAcceptedOfferUpdatedNotification($order));
            }

            DB::commit();

            // Notify the user about the new offer
            // $order->user->notify(new \App\Notifications\OrderOfferSubmitted($order));
            return redirect()->route('provider.orders.show', $order)->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }

    }

    public function offers(Request $request)
    {
        $authUser = auth('provider')->user();
        $rows = $authUser->orderOffers()
            ->with(['order'])
            ->latest()
            ->paginate($request->integer('perPage', 16));

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
        if ($offer->order()->isNot($order) || $offer->provider()->isNot(auth()->user())) {
            return redirect()->back()->with('error', __('sorry this offer does not belong to this order.'));
        }
        if ($offer->status->is(OfferStatusEnum::Pending)) {
            return redirect()->back()->with('error', __('you can not delete this offer because it has been processed.'));
        }
        $offer->delete();

        return redirect()->route('provider.orders.show', $order)->with('success', __('data deleted successfully'));
    }

    public function end(Order $order): RedirectResponse
    {
        if ($order->provider()->isNot(auth()->user())) {
            abort(404);
        }

        if ($order->status->isNotIn([OrderStatusEnum::InProgress])) {
            return redirect()->back()->with('error', __('you can not ed this order'));
        }

        $order->update(['status' => OrderStatusEnum::EndedByProvider]);

        return redirect()->back()->with('success', __('data updated successfully'));
    }

    public function updateReview(Order $order, OrderReviewRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($order->status !== OrderStatusEnum::EndedByClient) {
            return redirect()->back()->with('error', __('you can not review this order'));
        }

        Review::updateOrCreate([
            'reviewer_type' => Provider::class,
            'reviewer_id' => $order->user_id,
            'operation_type' => Order::class,
            'operation_id' => $order->id,
        ], [
            'reviewee_type' => User::class,
            'reviewee_id' => auth()->user()->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
        ]);

        return redirect()->back()->with('success', __('data updated successfully'));
    }
}
