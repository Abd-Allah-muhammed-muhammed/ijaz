<?php

namespace Modules\Wallet\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Exceptions\InsufficientBalanceException;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Http\Requests\Provider\WithdrawRequestRequest;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawCollection;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawResource;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WithdrawRequestService;
use Throwable;

class WithdrawController extends Controller
{
    public function __construct(
        private readonly WithdrawRequestService $withdrawRequestService,
    ) {}

    public function index(Request $request): Response
    {
        $rows = $this->withdrawRequestService->listForOwner(
            auth('provider')->user(),
            $request->integer('perPage', 16),
        );

        return inertia('Provider/WithdrawRequests/Index', [
            'rows' => fn () => WithdrawCollection::make($rows),
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    public function show(WithdrawRequest $withdrawRequest): Response
    {
        return inertia('Provider/WithdrawRequests/Show', [
            'row' => WithdrawResource::make($withdrawRequest),
            'paymentResponse' => null,
        ]);
    }

    public function store(WithdrawRequestRequest $request): RedirectResponse
    {
        $provider = auth('provider')->user();
        $data = CreateWithdrawData::fromRequest($request->validated());

        DB::beginTransaction();
        try {
            $this->withdrawRequestService->create($provider, $data);
            DB::commit();

            return redirect()->back()
                ->with('success', trans('Withdraw request created successfully and is pending admin approval.'));
        } catch (InsufficientBalanceException $e) {
            DB::rollBack();

            return redirect()->back()->with('error', __("You can't withdraw this amount."));
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function destroy(WithdrawRequest $withdrawRequest): RedirectResponse
    {
        $provider = auth('provider')->user();

        DB::beginTransaction();
        try {
            $this->withdrawRequestService->cancel($provider, $withdrawRequest);
            DB::commit();

            return redirect()->route('provider.withdraw-requests.index')
                ->with('success', __('data deleted successfully'));
        } catch (WalletException $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
