<?php

namespace Modules\Wallet\Http\Controllers\Dashboard;

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResponseResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payment\DTOs\PaymentResponse;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Services\PaymentService;
use Modules\Wallet\Http\Requests\Dashboard\UpdateTopUpStatusRequest;
use Modules\Wallet\Http\Resources\Dashboard\TopUpCollection;
use Modules\Wallet\Http\Resources\Dashboard\TopUpResource;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Services\TopUpRequestService;
use Modules\Wallet\Services\WalletService;

class TopUpRequestController extends Controller
{
    public function __construct(
        private readonly TopUpRequestService $topUpRequestService,
        private readonly WalletService $walletService,
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request): Response
    {
        $rows = $this->topUpRequestService->listAll(
            $request->integer('perPage', 16),
        );

        return inertia('Dashboard/TopUpRequests/Index', [
            'rows' => fn () => TopUpCollection::make($rows),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    public function show(TopUpRequest $topUpRequest): Response
    {
        $topUpRequest->load('user');

        return inertia('Dashboard/TopUpRequests/Show', [
            'row' => TopUpResource::make($topUpRequest),
            'paymentResponse' => Inertia::defer(fn () => $this->resolvePaymentResponse($topUpRequest)),
        ]);
    }

    public function updateStatus(
        TopUpRequest $topUpRequest,
        UpdateTopUpStatusRequest $request,
    ): RedirectResponse {
        if ($topUpRequest->status !== OperationStatusEnum::Pending) {
            return redirect()->back()->with('error', __('you can not update this top up request status'));
        }

        DB::transaction(function () use ($request, $topUpRequest) {
            $topUpRequest->update([
                'status' => $request->validated('status'),
                'admin_notes' => $request->validated('admin_notes'),
                'admin_id' => auth('admin')->id(),
            ]);

            if (
                $topUpRequest->status === OperationStatusEnum::Approved
                && $topUpRequest->payment_method->isOffline()
            ) {
                $this->walletService->credit(
                    owner: $topUpRequest->user,
                    amount: $topUpRequest->amount,
                    operation: $topUpRequest,
                    description: "Offline top-up approved #{$topUpRequest->id}",
                );
            }
        });

        return redirect()->route('dashboard.top-up-requests.index')->with('success', __('data saved successfully'));
    }

    private function resolvePaymentResponse(TopUpRequest $topUpRequest): ?PaymentResponseResource
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
