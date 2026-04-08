<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateWithdrawRequestStatusRequest;
use App\Http\Resources\Dashboard\WithdrawCollection;
use App\Http\Resources\Dashboard\WithdrawResource;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Throwable;

class WithdrawRequestController extends Controller
{
    use HasApiResponse;

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

    public function updateStatus(WithdrawRequest $withdrawRequest, UpdateWithdrawRequestStatusRequest $request)
    {
        $data = $request->validated();
        $admin = auth('admin')->user();
        if ($withdrawRequest->status !== OperationStatusEnum::Pending) {
            return redirect()->back()->with('error', __('you can not update this withdraw request status'));
        }

        DB::beginTransaction();
        try {
            $withdrawRequest->update([
                ...$data,
                'admin_id' => $admin->id,
            ]);
            $user = $withdrawRequest->user;
            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate();

            $user->walletTTransactions()->create([
                'wallet_id' => $wallet->id,
                'debit' => $withdrawRequest->status === OperationStatusEnum::Approved ? $withdrawRequest->amount : 0,
                'credit' => 0,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance - ($withdrawRequest->status === OperationStatusEnum::Approved ? $withdrawRequest->amount : 0),
                'operation_type' => get_class($withdrawRequest),
                'operation_id' => $withdrawRequest->id,
                'pending_credit' => 0,
                'description' => 'Wallet withdraw for '.get_class($withdrawRequest).' #'.$withdrawRequest->id,
                'pending_debit' => -$withdrawRequest->amount,
            ]);

            $wallet->pending_debit -= $withdrawRequest->amount;
            $wallet->balance -= $withdrawRequest->status === OperationStatusEnum::Approved ? $withdrawRequest->amount : 0;
            $wallet->debit += $withdrawRequest->status === OperationStatusEnum::Approved ? $withdrawRequest->amount : 0;
            $wallet->save();

            DB::commit();

            return redirect()->route('dashboard.withdraw-requests.index')->with('success', __('data saved successfully'));

        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
