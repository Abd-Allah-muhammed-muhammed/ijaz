<?php

namespace Modules\Wallet\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Http\Requests\Dashboard\UpdateWithdrawStatusRequest;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawCollection;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawResource;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WithdrawRequestService;

class WithdrawRequestController extends Controller
{
    public function __construct(
        private readonly WithdrawRequestService $withdrawRequestService,
    ) {}

    public function index(Request $request): Response
    {
        $rows = $this->withdrawRequestService->listAll(
            $request->integer('perPage', 16),
        );

        return inertia('Dashboard/WithdrawRequests/Index', [
            'rows' => fn () => WithdrawCollection::make($rows),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    public function show(WithdrawRequest $withdrawRequest): Response
    {
        $withdrawRequest->load('user');

        return inertia('Dashboard/WithdrawRequests/Show', [
            'row' => WithdrawResource::make($withdrawRequest),
        ]);
    }

    public function updateStatus(
        WithdrawRequest $withdrawRequest,
        UpdateWithdrawStatusRequest $request,
    ): RedirectResponse {
        try {
            $this->withdrawRequestService->updateStatusForDashboard(
                $withdrawRequest,
                $request->validated('status'),
                $request->validated('admin_notes'),
                (int) auth('admin')->id(),
            );
        } catch (WalletException $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }

        return redirect()->route('dashboard.withdraw-requests.index')->with('success', __('data saved successfully'));
    }
}
