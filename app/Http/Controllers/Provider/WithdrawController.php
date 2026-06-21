<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WithdrawRequestRequest;
use App\Http\Resources\Dashboard\WithdrawCollection;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawResource;
use App\Http\Resources\PayTapResponseResource;
use Modules\Wallet\Models\WithdrawRequest;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lib\Payment\Facade\Payment;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Throwable;

class WithdrawController extends Controller
{
    use HasApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $rows = auth('provider')->user()->withdrawRequests()
            ->latest()
            ->paginate($request->integer('perPage', 16));

        return inertia('Provider/WithdrawRequests/Index', [
            'rows' => fn () => WithdrawCollection::make($rows),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(WithdrawRequest $withdrawRequest)
    {

        return inertia('Provider/WithdrawRequests/Show', [
            'row' => WithdrawResource::make($withdrawRequest),
            'paymentResponse' => Inertia::defer(static function () use ($withdrawRequest) {
                if (! $withdrawRequest->transaction_id || ! $withdrawRequest->payment_driver) {
                    return null;
                }
                $response = Payment::driver($withdrawRequest->payment_driver)->get($withdrawRequest->transaction_id);

                //        return $response;
                return PayTapResponseResource::make($response);
            }),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(WithdrawRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = auth('provider')->user();
        if ($data['amount'] > ($user->wallet->balance - $user->wallet->pending_debit)) {
            return redirect()->back()->with('error', __('You can\'t withdraw this amount.'));
        }
        DB::beginTransaction();
        try {
            /**
             * @var WithdrawRequest $withdrawRequest
             */
            $withdrawRequest = $user->withdrawRequests()->create($data);
            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate();

            $user->walletTransactions()->create([
                'wallet_id' => $wallet->id,
                'balance_after' => $wallet->balance,
                'balance_before' => $wallet->balance,
                'operation_type' => get_class($withdrawRequest),
                'operation_id' => $withdrawRequest->id,
                'description' => 'Withdraw Request Created #'.$withdrawRequest->id,
                'pending_debit' => $withdrawRequest->amount,
                'pending_credit' => 0,
            ]);

            $wallet->pending_debit += $withdrawRequest->amount;
            $wallet->save();

            DB::commit();

            return redirect()->back()->with('success', trans('Withdraw request created successfully and is pending admin approval.'));
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function destroy(WithdrawRequest $withdrawRequest)
    {
        if (! $withdrawRequest->status->isPending()) {
            return $this->failedMessageResponse(__('Only pending withdraw requests can be deleted.'));
        }

        $user = auth('provider')->user();

        DB::beginTransaction();
        try {
            $withdrawRequest->delete();

            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate();

            $user->walletTransactions()->create([
                'wallet_id' => $wallet->id,
                'balance_after' => $wallet->balance,
                'balance_before' => $wallet->balance,
                'operation_type' => get_class($withdrawRequest),
                'operation_id' => $withdrawRequest->id,
                'description' => 'Withdraw Request Deleted #'.$withdrawRequest->id,
                'pending_debit' => -$withdrawRequest->amount,
                'pending_credit' => 0,
            ]);

            $wallet->pending_debit -= $withdrawRequest->amount;
            $wallet->save();

            DB::commit();

            return redirect()->route('provider.withdraw-requests.index')->with('success', __('data deleted successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
