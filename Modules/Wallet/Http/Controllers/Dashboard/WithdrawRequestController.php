<?php

namespace Modules\Wallet\Http\Controllers\Dashboard;

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Http\Requests\Dashboard\UpdateWithdrawStatusRequest;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawCollection;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawResource;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;
use Throwable;

class WithdrawRequestController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $rows = WithdrawRequest::query()
            ->with(['user'])
            ->orderBy(DB::raw('status = "'.OperationStatusEnum::Pending->value.'"'), 'DESC')
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('perPage', 16));

        return inertia('Dashboard/WithdrawRequests/Index', [
            'rows' => fn () => WithdrawCollection::make($rows),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(WithdrawRequest $withdrawRequest)
    {
        $withdrawRequest->load(['user']);

        return inertia('Dashboard/WithdrawRequests/Show', [
            'row' => WithdrawResource::make($withdrawRequest),
        ]);
    }

    public function updateStatus(WithdrawRequest $withdrawRequest, UpdateWithdrawStatusRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $admin = auth('admin')->user();

        if ($withdrawRequest->status !== OperationStatusEnum::Pending) {
            return redirect()->back()->with('error', __('you can not update this withdraw request status'));
        }

        $approved = $data['status'] === OperationStatusEnum::Approved->value;

        try {
            DB::transaction(function () use ($data, $withdrawRequest, $admin, $approved) {
                $withdrawRequest->update([
                    ...$data,
                    'admin_id' => $admin->id,
                ]);

                $this->walletService->finalizeWithdraw(
                    $withdrawRequest->user,
                    $withdrawRequest,
                    $approved,
                );
            });

            return redirect()->route('dashboard.withdraw-requests.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
