<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateTopUpRequestStatusRequest;
use App\Http\Resources\Dashboard\TopUpCollection;
use App\Http\Resources\Dashboard\TopUpResource;
use App\Http\Resources\PayTapResponseResource;
use Modules\Wallet\Models\TopUpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Lib\Payment\Facade\Payment;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Throwable;

class TopUpRequestController extends Controller
{
    use HasApiResponse;

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
            'paymentResponse' => Inertia::defer(static function () use ($topUpRequest) {
                if (! $topUpRequest->transaction_id || ! $topUpRequest->payment_driver) {
                    return null;
                }
                $response = Payment::driver($topUpRequest->payment_driver)->get($topUpRequest->transaction_id);

                return PayTapResponseResource::make($response);
            }),
        ]);
    }

    public function updateStatus(TopUpRequest $topUpRequest, UpdateTopUpRequestStatusRequest $request)
    {
        $data = $request->validated();
        if ($topUpRequest->status !== OperationStatusEnum::Pending) {
            return redirect()->back()->with('error', __('you can not update this top up request status'));
        }

        try {
            $topUpRequest->update([
                ...$data,
                'admin_id' => auth()->user()->id,
            ]);
            $user = $topUpRequest->user;
            if ($topUpRequest->status === OperationStatusEnum::Approved) {
                $wallet = $user->wallet()->lockForUpdate()->firstOrCreate();
                $credit = $topUpRequest->amount;
                $description = 'Wallet top-up for '.get_class($topUpRequest).' #'.$topUpRequest->id;
                $balance_before = $wallet->balance;
                $balance_after = $wallet->balance + $credit;
                $wallet->increment('balance', $credit);

                $user->walletTransactions()->create([
                    'wallet_id' => $wallet->id,
                    'debit' => 0,
                    'credit' => $credit,
                    'balance_after' => $balance_after,
                    'balance_before' => $balance_before,
                    'operation_type' => get_class($topUpRequest),
                    'operation_id' => $topUpRequest->id,
                    'pending_credit' => 0,
                    'description' => $description,
                    'pending_debit' => 0,
                ]);
            }
            DB::commit();

            return redirect()->route('dashboard.top-up-requests.index')->with('success', __('data saved successfully'));

        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
