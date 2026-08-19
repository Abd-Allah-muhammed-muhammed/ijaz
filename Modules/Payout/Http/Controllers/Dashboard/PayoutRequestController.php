<?php

namespace Modules\Payout\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Payout\Exceptions\PayoutException;
use Modules\Payout\Http\Requests\Dashboard\ConfirmPayoutTransferRequest;
use Modules\Payout\Http\Requests\Dashboard\FailPayoutTransferRequest;
use Modules\Payout\Http\Resources\Dashboard\PayoutRequestCollection;
use Modules\Payout\Models\PayoutRequest;
use Modules\Payout\Services\PayoutService;

class PayoutRequestController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PayoutService $payoutService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:confirm payouts', only: ['index', 'confirm', 'fail']),
        ];
    }

    public function index(Request $request): Response
    {
        $rows = $this->payoutService->listPendingForDashboard($request);

        return inertia('Dashboard/PayoutRequests/Index', [
            'rows' => PayoutRequestCollection::make($rows),
            'prams' => $request->all() ?: [],
        ]);
    }

    public function confirm(
        PayoutRequest $payoutRequest,
        ConfirmPayoutTransferRequest $request,
    ): RedirectResponse {
        try {
            $this->payoutService->confirmTransfer(
                $payoutRequest,
                (int) auth('admin')->id(),
                $request->validated('gateway_reference'),
                $request->file('proof_image'),
            );
        } catch (PayoutException $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }

        return redirect()->route('dashboard.payout-requests.index')->with('success', __('data saved successfully'));
    }

    public function fail(
        PayoutRequest $payoutRequest,
        FailPayoutTransferRequest $request,
    ): RedirectResponse {
        try {
            $this->payoutService->failTransfer(
                $payoutRequest,
                $request->validated('failure_reason'),
            );
        } catch (PayoutException $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }

        return redirect()->route('dashboard.payout-requests.index')->with('success', __('data saved successfully'));
    }
}
