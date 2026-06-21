<?php

namespace App\Http\Controllers\Provider;

use App\Enums\OperationStatusEnum;
use App\Enums\Payment\PaymentDriverEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TopUpRequestRequest;
use App\Http\Resources\Dashboard\TopUpCollection;
use App\Http\Resources\Dashboard\TopUpResource;
use App\Http\Resources\PayTapResponseResource;
use Modules\Wallet\Models\TopUpRequest;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lib\Payment\Facade\Payment;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Throwable;

class TopUpController extends Controller
{
    use HasApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $rows = auth('provider')->user()->topUpRequests()
            ->latest()
            ->paginate($request->integer('perPage', 16));

        return inertia('Provider/TopUpRequests/Index', [
            'rows' => fn () => TopUpCollection::make($rows),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(TopUpRequest $topUpRequest)
    {

        return inertia('Provider/TopUpRequests/Show', [
            'row' => TopUpResource::make($topUpRequest),
            'paymentResponse' => Inertia::defer(static function () use ($topUpRequest) {
                if (! $topUpRequest->transaction_id || ! $topUpRequest->payment_driver) {
                    return null;
                }
                $response = Payment::driver($topUpRequest->payment_driver)->get($topUpRequest->transaction_id);

                //        return $response;
                return PayTapResponseResource::make($response);
            }),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(TopUpRequestRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = auth('provider')->user();
        DB::beginTransaction();
        try {
            /**
             * @var PaymentMethodEnum $paymentMethod
             */
            $paymentMethod = $request->enum('payment_method', PaymentMethodEnum::class);

            if ($paymentMethod->isOffline() && $request->hasFile('transaction_image')) {
                $data['transaction_image'] = $request->file('transaction_image')?->store('topup', 'local');
            }

            if ($paymentMethod->isOnline()) {
                $data['payment_status'] = PaymentStatusEnum::Pending;
            }

            /**
             * @var TopUpRequest $topUpRequest
             */
            $topUpRequest = $user->topUpRequests()->create([
                ...$data,
                'status' => OperationStatusEnum::Pending,
            ]);

            if ($paymentMethod->isOnline()) {
                /**
                 * @var PaymentDriverEnum $paymentDriver
                 */
                $paymentDriver = $request->enum('payment_driver', PaymentDriverEnum::class);
                $payment = $user->payments()->create([
                    'status' => PaymentStatusEnum::Pending,
                    'amount' => $topUpRequest->amount,
                    'driver' => $paymentDriver,
                    'product_type' => get_class($topUpRequest),
                    'product_id' => $topUpRequest->id,
                ]);
                $paymentResponse = Payment::driver($paymentDriver->value)->pay($payment);
                DB::commit();

                return $this->successResponse($paymentResponse->toArray());
            }
            DB::commit();

            return $this->successResponse([
                'status' => 'pending',
                'transaction_id' => '',
                'driver' => '',
                'url' => '',
                'payable' => false,
                'data' => [],
                'message' => trans('Top up request created successfully and is pending admin approval.'),
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
            report($th);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function destroy(TopUpRequest $topUpRequest)
    {
        if (! $topUpRequest->status->isPending()) {
            return $this->failedMessageResponse(__('Only pending top-up requests can be deleted.'));
        }
        $topUpRequest->delete();

        return redirect()->route('provider.top-up-requests.index')->with('success', __('data deleted successfully'));
    }
}
