<?php

namespace Modules\Wallet\Http\Controllers\Dashboard;

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PayTapResponseResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Payment\DTOs\PaymentResponse;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Services\PaymentService;
use Modules\Wallet\Http\Requests\Dashboard\UpdateTopUpStatusRequest;
use Modules\Wallet\Http\Resources\Dashboard\TopUpCollection;
use Modules\Wallet\Http\Resources\Dashboard\TopUpResource;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Services\WalletService;
use Throwable;

class TopUpRequestController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $rows = TopUpRequest::query()
            ->with(['user'])
            ->orderBy(DB::raw('status = "'.OperationStatusEnum::Pending->value.'"'), 'DESC')
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('perPage', 16));

        return inertia('Dashboard/TopUpRequests/Index', [
            'rows' => fn () => TopUpCollection::make($rows),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(TopUpRequest $topUpRequest)
    {
        $topUpRequest->load(['user']);

        return inertia('Dashboard/TopUpRequests/Show', [
            'row' => TopUpResource::make($topUpRequest),
            'paymentResponse' => Inertia::defer(fn () => $this->resolvePaymentResponse($topUpRequest)),
        ]);
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

    public function updateStatus(TopUpRequest $topUpRequest, UpdateTopUpStatusRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($topUpRequest->status !== OperationStatusEnum::Pending) {
            return redirect()->back()->with('error', __('you can not update this top up request status'));
        }

        try {
            DB::transaction(function () use ($data, $topUpRequest) {
                $topUpRequest->update([
                    ...$data,
                    'admin_id' => auth('admin')->id(),
                ]);

                if (
                    $topUpRequest->status === OperationStatusEnum::Approved
                    && $topUpRequest->payment_method->isOffline()
                ) {
                    $this->walletService->credit(
                        $topUpRequest->user,
                        (float) $topUpRequest->amount,
                        $topUpRequest,
                        'Wallet top-up for '.get_class($topUpRequest).' #'.$topUpRequest->id,
                    );
                }
            });

            return redirect()->route('dashboard.top-up-requests.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
