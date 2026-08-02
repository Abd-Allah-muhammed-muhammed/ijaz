<?php

namespace Modules\Wallet\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Payment\DTOs\PaymentResponse;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Http\Resources\PaymentResponseResource;
use Modules\Payment\Services\PaymentService;
use Modules\Wallet\DTOs\CreateTopUpData;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Http\Requests\Provider\TopUpRequestRequest;
use Modules\Wallet\Http\Resources\Dashboard\TopUpCollection;
use Modules\Wallet\Http\Resources\Dashboard\TopUpResource;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Services\TopUpRequestService;
use Throwable;

class TopUpController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly TopUpRequestService $topUpRequestService,
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request): Response
    {
        $rows = $this->topUpRequestService->listForOwner(
            auth('provider')->user(),
            $request,
        );

        return inertia('Provider/TopUpRequests/Index', [
            'rows' => fn () => TopUpCollection::make($rows),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    public function show(TopUpRequest $topUpRequest): Response
    {
        return inertia('Provider/TopUpRequests/Show', [
            'row' => TopUpResource::make($topUpRequest),
            'paymentResponse' => $this->hasResolvableCardData($topUpRequest)
                ? Inertia::defer(fn () => $this->resolvePaymentResponse($topUpRequest))
                : null,
        ]);
    }

    public function store(TopUpRequestRequest $request): JsonResponse|RedirectResponse
    {
        $provider = auth('provider')->user();
        $imagePath = null;

        if ($request->hasFile('transaction_image')) {
            $imagePath = $request->file('transaction_image')
                ->store('topup', 'public');
        }

        $data = CreateTopUpData::fromRequest($request->validated(), $imagePath);

        DB::beginTransaction();
        try {
            $result = $this->topUpRequestService->create($provider, $data);
            $paymentResult = $result['paymentResult'];
            DB::commit();

            if ($paymentResult !== null) {
                if (! $paymentResult->isSuccessful()) {
                    return $this->failedMessageResponse($paymentResult->message);
                }

                return $this->successResponse($paymentResult->toArray());
            }

            return $this->successResponse([
                'status' => 'pending',
                'transaction_id' => '',
                'driver' => '',
                'url' => '',
                'payable' => false,
                'data' => [],
                'message' => trans('Top up request created successfully and is pending admin approval.'),
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return $this->failedMessageResponse(__('something went wrong'));
        }
    }

    public function destroy(TopUpRequest $topUpRequest): RedirectResponse
    {
        try {
            $this->topUpRequestService->cancel($topUpRequest);

            return redirect()->route('provider.top-up-requests.index')
                ->with('success', __('data deleted successfully'));
        } catch (WalletException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function hasResolvableCardData(TopUpRequest $topUpRequest): bool
    {
        return filled($topUpRequest->transaction_id) && filled($topUpRequest->payment_driver);
    }

    private function resolvePaymentResponse(TopUpRequest $topUpRequest): ?PaymentResponseResource
    {
        if (! $this->hasResolvableCardData($topUpRequest)) {
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

        return PaymentResponseResource::make(new PaymentResponse(
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
