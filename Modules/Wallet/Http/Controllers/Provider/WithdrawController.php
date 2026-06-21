<?php

namespace Modules\Wallet\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WithdrawRequestRequest;
use App\Http\Resources\Dashboard\WithdrawCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawResource;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;
use Throwable;

class WithdrawController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly WalletService $walletService,
    ) {}

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
            'paymentResponse' => null,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(WithdrawRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = auth('provider')->user();

        DB::beginTransaction();
        try {
            if (! $this->walletService->canWithdraw($user, (float) $data['amount'])) {
                DB::rollBack();

                return redirect()->back()->with('error', __('You can\'t withdraw this amount.'));
            }

            /** @var WithdrawRequest $withdrawRequest */
            $withdrawRequest = $user->withdrawRequests()->create($data);

            $this->walletService->addPendingDebit(
                $user,
                (float) $data['amount'],
                $withdrawRequest,
                'Withdraw Request Created #'.$withdrawRequest->id,
            );

            DB::commit();

            return redirect()->back()->with('success', trans('Withdraw request created successfully and is pending admin approval.'));
        } catch (Throwable $th) {
            DB::rollBack();
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
            $this->walletService->reversePendingDebit(
                $user,
                (float) $withdrawRequest->amount,
                $withdrawRequest,
                'Withdraw Request Deleted #'.$withdrawRequest->id,
            );

            $withdrawRequest->delete();

            DB::commit();

            return redirect()->route('provider.withdraw-requests.index')->with('success', __('data deleted successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
