<?php

namespace Modules\Wallet\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payment\DTOs\PaymentResponse;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Http\Resources\PaymentResponseResource;
use Modules\Payment\Services\PaymentService;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Http\Requests\Dashboard\UpdateTopUpStatusRequest;
use Modules\Wallet\Http\Resources\Dashboard\TopUpCollection;
use Modules\Wallet\Http\Resources\Dashboard\TopUpResource;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Services\TopUpRequestService;

class TopUpRequestController extends Controller
{
    public function __construct(
        private readonly TopUpRequestService $topUpRequestService,
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
        try {
            $this->topUpRequestService->updateStatusForDashboard(
                $topUpRequest,
                $request->validated('status'),
                $request->validated('admin_notes'),
                (int) auth('admin')->id(),
            );
        } catch (WalletException $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }

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
