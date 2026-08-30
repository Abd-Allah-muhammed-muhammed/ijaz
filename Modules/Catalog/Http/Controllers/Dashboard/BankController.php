<?php

namespace Modules\Catalog\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Catalog\Contracts\Services\BankServiceInterface;
use Modules\Catalog\DTOs\StoreBankDTO;
use Modules\Catalog\DTOs\UpdateBankDTO;
use Modules\Catalog\Http\Requests\Dashboard\BankRequest;
use Modules\Catalog\Http\Resources\Dashboard\BankCollection;
use Modules\Catalog\Http\Resources\Dashboard\BankResource;
use Modules\Catalog\Models\Bank;
use Throwable;

class BankController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly BankServiceInterface $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show banks', only: ['index']),
            new Middleware('permission:create banks', only: ['create', 'store']),
            new Middleware('permission:edit banks', only: ['edit', 'update']),
            new Middleware('permission:delete banks', only: ['destroy', 'restore']),
        ];
    }

    public function index(Request $request): Response
    {
        $rows = $this->service->index($request);

        return inertia('Dashboard/Banks/Index', [
            'prams' => $request->all() ?: [],
            'rows' => BankCollection::make($rows),
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/Banks/Create');
    }

    /**
     * @throws Throwable
     */
    public function store(BankRequest $request): RedirectResponse
    {
        try {
            $this->service->store(StoreBankDTO::fromValidated(
                $request->validated(),
                $request->file('logo'),
            ));

            return redirect()->route('dashboard.banks.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function edit(Bank $bank): Response
    {
        $bank = $this->service->show($bank);

        return inertia('Dashboard/Banks/Edit', [
            'row' => BankResource::make($bank),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function update(BankRequest $request, Bank $bank): RedirectResponse
    {
        try {
            $this->service->update($bank, UpdateBankDTO::fromValidated(
                $request->validated(),
                $request->file('logo'),
            ));

            return redirect()->route('dashboard.banks.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function destroy(Bank $bank): RedirectResponse
    {
        $this->service->destroy($bank);

        return redirect()->route('dashboard.banks.index')->with('success', __('data deleted successfully'));
    }

    public function restore(Bank $bank): RedirectResponse
    {
        $this->service->restore($bank);

        return redirect()->back()->with('success', __('data restored successfully'));
    }
}
