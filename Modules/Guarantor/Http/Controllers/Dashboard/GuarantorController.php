<?php

namespace Modules\Guarantor\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorStatusAction;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Http\Resources\Dashboard\GuarantorDashboardCollection;
use Modules\Guarantor\Http\Resources\Dashboard\GuarantorDashboardResource;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

class GuarantorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show guarantors', only: ['index', 'show']),
            new Middleware('permission:manage guarantors', only: ['updateStatus', 'releaseInstallment']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/Guarantor/Index', [
            'rows' => fn () => GuarantorDashboardCollection::make(
                GuarantorRequest::query()
                    ->with(['requester', 'counterparty', 'media'])
                    ->withCount(['installments'])
                    ->when($request->search, fn ($query) => $query->where('title', 'like', "%{$request->search}%"))
                    ->when($request->status, fn ($query) => $query->where('status', $request->status))
                    ->when($request->type, fn ($query) => $query->where('type', $request->type))
                    ->when($request->date_from, fn ($query) => $query->whereDate('created_at', '>=', $request->date_from))
                    ->when($request->date_to, fn ($query) => $query->whereDate('created_at', '<=', $request->date_to))
                    ->latest()
                    ->paginate($request->integer('per_page', 15))
                    ->withQueryString()
            ),
            'prams' => fn () => $request->all() ?: [],
            'selects' => fn () => [
                'statuses' => GuarantorStatusEnum::collect()
                    ->map(fn ($status) => $status->toArray())
                    ->values(),
                'types' => GuarantorTypeEnum::collect()
                    ->map(fn ($type) => $type->toArray())
                    ->values(),
            ],
            'stats' => fn () => [
                'total' => GuarantorRequest::count(),
                'new' => GuarantorRequest::where('status', GuarantorStatusEnum::New)->count(),
                'in_progress' => GuarantorRequest::where('status', GuarantorStatusEnum::InProgress)->count(),
                'overdue' => GuarantorRequest::where('status', GuarantorStatusEnum::Overdue)->count(),
                'ended' => GuarantorRequest::where('status', GuarantorStatusEnum::Ended)->count(),
            ],
        ]);
    }

    public function show(GuarantorRequest $guarantorRequest): Response
    {
        $guarantorRequest->load([
            'requester',
            'counterparty',
            'installments',
            'companyDetail.region',
            'companyDetail.city',
            'companyDetail.media',
            'statusHistories.actor',
            'media',
        ]);

        return inertia('Dashboard/Guarantor/Show', [
            'guarantorRequest' => fn () => new GuarantorDashboardResource($guarantorRequest),
            'selects' => fn () => [
                'statuses' => GuarantorStatusEnum::collect()
                    ->map(fn ($status) => $status->toArray())
                    ->values(),
            ],
        ]);
    }

    public function updateStatus(Request $request, GuarantorRequest $guarantorRequest): RedirectResponse
    {
        $request->validate([
            'status' => ['required', Rule::enum(GuarantorStatusEnum::class)],
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data = new UpdateGuarantorStatusData(
            status: GuarantorStatusEnum::from($request->string('status')->toString()),
            reason: $request->string('reason')->toString(),
            notes: $request->input('notes'),
        );

        app(UpdateGuarantorStatusAction::class)->handle(
            $guarantorRequest,
            $data,
            auth('admin')->user(),
            'admin'
        );

        return back()->with('success', __('guarantor.status_updated_successfully'));
    }

    public function releaseInstallment(
        GuarantorRequest $guarantorRequest,
        GuarantorInstallment $installment,
    ): RedirectResponse {
        app(ReleaseInstallmentAction::class)->handle($installment, 'admin');

        return back()->with('success', __('guarantor.installment_released_successfully'));
    }

    public function destroy(GuarantorRequest $guarantorRequest): RedirectResponse
    {
        $guarantorRequest->delete();

        return redirect()
            ->route('dashboard.guarantor.index')
            ->with('success', __('guarantor.deleted_successfully'));
    }
}
