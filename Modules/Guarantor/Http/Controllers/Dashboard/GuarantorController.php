<?php

namespace Modules\Guarantor\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Http\Requests\ListConversationMessagesRequest;
use Modules\Chat\Http\Requests\SendSupportMessageRequest;
use Modules\Chat\Http\Resources\ConversationMessageCollection;
use Modules\Chat\Http\Resources\ConversationMessageResource;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Http\Requests\Dashboard\ApproveGuarantorRequest;
use Modules\Guarantor\Http\Requests\Dashboard\CancelGuarantorRequest;
use Modules\Guarantor\Http\Requests\Dashboard\RejectGuarantorRequest;
use Modules\Guarantor\Http\Requests\Dashboard\ResolveGuarantorDisputeRequest;
use Modules\Guarantor\Http\Resources\Dashboard\GuarantorDashboardCollection;
use Modules\Guarantor\Http\Resources\Dashboard\GuarantorDashboardResource;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Services\GuarantorDashboardService;

class GuarantorController extends Controller implements HasMiddleware
{
    use HasApiResponse;

    public function __construct(
        private readonly GuarantorDashboardService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show guarantors', only: [
                'index',
                'show',
                'conversationMessages',
            ]),
            new Middleware('permission:manage guarantors', only: [
                'approveByAdmin',
                'rejectByAdmin',
                'cancel',
                'resolveDispute',
                'releaseInstallment',
                'destroy',
                'sendConversationMessage',
                'conversationTyping',
            ]),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/Guarantor/Index', [
            'rows' => fn () => GuarantorDashboardCollection::make(
                $this->service->listAll($request, $request->integer('per_page', 15))
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
            'stats' => fn () => $this->service->getStats(),
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
            'conversation.user1',
            'conversation.user2',
        ]);

        return inertia('Dashboard/Guarantor/Show', [
            'guarantorRequest' => fn () => new GuarantorDashboardResource($guarantorRequest),
        ]);
    }

    public function conversationMessages(
        ListConversationMessagesRequest $request,
        GuarantorRequest $guarantorRequest,
    ): JsonResponse {
        $messages = $this->service->conversationMessages(
            $guarantorRequest,
            $request->integer('per_page', 15),
            $request->searchTerm(),
        );

        if ($messages === null) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return $this->successResponse(
            ConversationMessageCollection::make($messages),
        );
    }

    public function sendConversationMessage(
        SendSupportMessageRequest $request,
        GuarantorRequest $guarantorRequest,
    ): JsonResponse {
        $message = $this->service->sendConversationMessageAsAdmin(
            $guarantorRequest,
            auth('admin')->user(),
            ChatMessageData::fromRequest($request),
        );

        return $this->successResponse(
            ConversationMessageResource::make($message)
        );
    }

    public function conversationTyping(GuarantorRequest $guarantorRequest): JsonResponse
    {
        $this->service->typingAsAdmin($guarantorRequest, auth('admin')->user());

        return $this->successResponse([]);
    }

    public function approveByAdmin(
        ApproveGuarantorRequest $request,
        GuarantorRequest $guarantorRequest,
    ): RedirectResponse {
        $this->service->approve($guarantorRequest, $request, auth('admin')->user());

        return back()->with('success', __('guarantor.status_updated_successfully'));
    }

    public function rejectByAdmin(
        RejectGuarantorRequest $request,
        GuarantorRequest $guarantorRequest,
    ): RedirectResponse {
        $this->service->reject($guarantorRequest, $request, auth('admin')->user());

        return back()->with('success', __('guarantor.status_updated_successfully'));
    }

    public function cancel(
        CancelGuarantorRequest $request,
        GuarantorRequest $guarantorRequest,
    ): RedirectResponse {
        $this->service->cancel($guarantorRequest, $request, auth('admin')->user());

        return back()->with('success', __('guarantor.status_updated_successfully'));
    }

    public function resolveDispute(
        ResolveGuarantorDisputeRequest $request,
        GuarantorRequest $guarantorRequest,
    ): RedirectResponse {
        $this->service->resolveDispute($guarantorRequest, $request, auth('admin')->user());

        return back()->with('success', __('guarantor.status_updated_successfully'));
    }

    public function releaseInstallment(
        GuarantorRequest $guarantorRequest,
        GuarantorInstallment $installment,
    ): RedirectResponse {
        $this->service->releaseInstallment($installment);

        return back()->with('success', __('guarantor.installment_released_successfully'));
    }

    public function destroy(GuarantorRequest $guarantorRequest): RedirectResponse
    {
        $this->service->delete($guarantorRequest);

        return redirect()
            ->route('dashboard.guarantor.index')
            ->with('success', __('guarantor.deleted_successfully'));
    }
}
