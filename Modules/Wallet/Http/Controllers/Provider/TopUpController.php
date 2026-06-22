<?php

namespace Modules\Wallet\Http\Controllers\Provider;

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PayTapResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Payment\DTOs\PaymentResponse;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Services\PaymentService;
use Modules\Wallet\Http\Requests\Provider\TopUpRequestRequest;
use Modules\Wallet\Http\Resources\Dashboard\TopUpCollection;
use Modules\Wallet\Http\Resources\Dashboard\TopUpResource;
use Modules\Wallet\Models\TopUpRequest;
use Throwable;

class TopUpController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

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
            'paymentResponse' => Inertia::defer(fn () => $this->resolvePaymentResponse($topUpRequest)),
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
            /** @var PaymentMethodEnum $paymentMethod */
            $paymentMethod = $request->enum('payment_method', PaymentMethodEnum::class);

            if ($paymentMethod->isOffline() && $request->hasFile('transaction_image')) {
                $data['transaction_image'] = $request->file('transaction_image')?->store('topup', 'local');
            }

            if ($paymentMethod->isOnline()) {
                $data['payment_status'] = PaymentStatusEnum::Pending;
            }

            /** @var TopUpRequest $topUpRequest */
            $topUpRequest = $user->topUpRequests()->create([
                ...$data,
                'status' => OperationStatusEnum::Pending,
                'wallet_id' => $user->wallet->id,
            ]);

            if ($paymentMethod->isOnline()) {
                $result = $this->paymentService->initiate(
                    owner: $user,
                    product: $topUpRequest,
                    amount: $topUpRequest->amount,
                    driver: $request->validated('payment_driver')
                        ?? $this->paymentService->getDefaultDriver(),
                );

                if (! $result->isSuccessful()) {
                    DB::rollBack();

                    return $this->failedMessageResponse($result->message);
                }

                DB::commit();

                return $this->successResponse($result->toArray());
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

    private function resolvePaymentResponse(TopUpRequest $topUpRequest): ?PayTapResponseResource
    {
        if (! $topUpRequest->transaction_id || ! $topUpRequest->payment_driver) {
            return null;
        }

        $payment = $topUpRequest->payment;

        if ($payment === null) {
            return null;
        }

        $rawResponse = $payment->response ?? [];

        if ($rawResponse === []) {
            $verifyResult = $this->paymentService
                ->resolveGateway($topUpRequest->payment_driver)
                ->verify($payment, ['tranRef' => $topUpRequest->transaction_id]);
            $rawResponse = $verifyResult->rawResponse;
        }

        return PayTapResponseResource::make(new PaymentResponse(
            status: $payment->status === PaymentStatusEnum::Accepted ? 'success' : $payment->status->value,
            transactionId: $topUpRequest->transaction_id,
            driver: $topUpRequest->payment_driver,
            url: '',
            payable: false,
            data: $rawResponse,
            message: $payment->message,
        ));
    }
}
