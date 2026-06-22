<?php

namespace Modules\Wallet\Http\Controllers\Dashboard;

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Modules\Wallet\Http\Requests\Dashboard\UpdateWithdrawStatusRequest;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawCollection;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawResource;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;
use Modules\Wallet\Services\WithdrawRequestService;

class WithdrawRequestController extends Controller
{
    public function __construct(
        private readonly WithdrawRequestService $withdrawRequestService,
        private readonly WalletService $walletService,
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
        if ($withdrawRequest->status !== OperationStatusEnum::Pending) {
            return redirect()->back()->with('error', __('you can not update this withdraw request status'));
        }

        $approved = $request->validated('status') === OperationStatusEnum::Approved->value;

        DB::transaction(function () use ($request, $withdrawRequest, $approved) {
            $withdrawRequest->update([
                'status' => $request->validated('status'),
                'admin_notes' => $request->validated('admin_notes'),
                'admin_id' => auth('admin')->id(),
            ]);

            $this->walletService->finalizeWithdraw(
                owner: $withdrawRequest->user,
                request: $withdrawRequest,
                approved: $approved,
            );
        });

        return redirect()->route('dashboard.withdraw-requests.index')->with('success', __('data saved successfully'));
    }
}
