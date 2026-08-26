<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Chat\Models\ConversationMessage;
use Modules\Guarantor\Actions\Chat\OpenGuarantorChatAction;
use Modules\Guarantor\Actions\Guarantor\OpenGuarantorDisputeAction;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Http\Controllers\Dashboard\GuarantorController as DashboardGuarantorController;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Notification::fake();
});

function createGuarantorDashboardAdmin(array $permissions = ['show guarantors', 'manage guarantors']): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Guarantor Dashboard Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $admin->givePermissionTo($permissions);

    return $admin;
}

function withoutGuarantorDashboardLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

test('admin can list guarantors', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);
    GuarantorRequest::factory()->count(2)->create();

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Guarantor/Index')
            ->has('rows.data', 2)
            ->has('stats')
        );
});

test('admin can view guarantor details', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Guarantor/Show')
            ->has('guarantorRequest')
            ->where('guarantorRequest.id', $guarantorRequest->id)
        );
});

test('admin can filter by status', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);

    GuarantorRequest::factory()->pendingAdmin()->create();
    $inProgress = GuarantorRequest::factory()->inProgress()->create(['title' => 'Filter Status Target']);

    $this->actingAs($admin, 'admin')
        ->get(route('dashboard.guarantor.index', [
            'status' => GuarantorStatusEnum::InProgress->value,
            'search' => 'Filter Status Target',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $inProgress->id)
        );
});

test('admin can filter by type', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);

    GuarantorRequest::factory()->create(['type' => GuarantorTypeEnum::Individual]);
    $company = GuarantorRequest::factory()->company()->create(['title' => 'Filter Type Target']);

    $this->actingAs($admin, 'admin')
        ->get(route('dashboard.guarantor.index', [
            'type' => GuarantorTypeEnum::Company->value,
            'search' => 'Filter Type Target',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $company->id)
        );
});

test('admin can approve pending request via dashboard', function () {
    $admin = createGuarantorDashboardAdmin(['show guarantors', 'manage guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->pendingAdmin()->create();

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->post(action([DashboardGuarantorController::class, 'approveByAdmin'], $guarantorRequest), [
            'notes' => 'Verified documents',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($guarantorRequest->fresh()->status)->toBe(GuarantorStatusEnum::ApprovedByAdmin);
});

test('admin can release installment', function () {
    $admin = createGuarantorDashboardAdmin(['show guarantors', 'manage guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $requester = $guarantorRequest->requester;
    $requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->post(action([DashboardGuarantorController::class, 'releaseInstallment'], [
            'guarantorRequest' => $guarantorRequest,
            'installment' => $installment,
        ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($installment->fresh()->status)->toBe(InstallmentStatusEnum::Released);
});

test('admin can delete guarantor request', function () {
    $admin = createGuarantorDashboardAdmin(['show guarantors', 'manage guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->create();

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'index']))
        ->delete(action([DashboardGuarantorController::class, 'destroy'], $guarantorRequest))
        ->assertRedirect(route('dashboard.guarantor.index'))
        ->assertSessionHas('success');

    expect(GuarantorRequest::withTrashed()->find($guarantorRequest->id)?->trashed())->toBeTrue();
});

test('non-admin cannot access dashboard', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin([]);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'index']))
        ->assertForbidden();
});

test('disputed guarantor show exposes disputed status and dispute reason history for the resolve UI', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors', 'manage guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->create([
        'status' => GuarantorStatusEnum::Disputed,
        'amount' => 1000,
        'fees' => 10,
    ]);
    $guarantorRequest->statusHistories()->create([
        'actor_id' => $guarantorRequest->requester_id,
        'actor_type' => $guarantorRequest->requester_type,
        'from_status' => GuarantorStatusEnum::InProgress->value,
        'to_status' => GuarantorStatusEnum::Disputed->value,
        'reason' => 'Goods not as agreed',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Guarantor/Show')
            ->where('guarantorRequest.status.value', GuarantorStatusEnum::Disputed->value)
            ->where('guarantorRequest.status_histories.0.to_status.value', GuarantorStatusEnum::Disputed->value)
            ->where('guarantorRequest.status_histories.0.reason', 'Goods not as agreed')
        );
});

test('admin with only show guarantors cannot resolve a dispute', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->create([
        'status' => GuarantorStatusEnum::Disputed,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->put(action([DashboardGuarantorController::class, 'resolveDispute'], $guarantorRequest), [
            'resolution' => GuarantorDisputeResolutionEnum::Escalate->value,
        ])
        ->assertForbidden();

    expect($guarantorRequest->fresh()->status)->toBe(GuarantorStatusEnum::Disputed);
});

test('admin with manage guarantors can resolve a disputed guarantor via each of the four resolution payloads', function () {
    withoutGuarantorDashboardLocaleMiddleware();

    $cases = [
        [
            'resolution' => GuarantorDisputeResolutionEnum::FullRequester->value,
            'payload' => ['resolution' => GuarantorDisputeResolutionEnum::FullRequester->value, 'notes' => 'full requester'],
            'expected' => GuarantorStatusEnum::EndedViaDispute,
        ],
        [
            'resolution' => GuarantorDisputeResolutionEnum::FullCounterparty->value,
            'payload' => ['resolution' => GuarantorDisputeResolutionEnum::FullCounterparty->value, 'notes' => 'full counterparty'],
            'expected' => GuarantorStatusEnum::CancelledViaDispute,
        ],
        [
            'resolution' => GuarantorDisputeResolutionEnum::Escalate->value,
            'payload' => ['resolution' => GuarantorDisputeResolutionEnum::Escalate->value, 'notes' => 'escalate'],
            'expected' => GuarantorStatusEnum::Escalated,
        ],
        [
            'resolution' => GuarantorDisputeResolutionEnum::PercentageSplit->value,
            'payload' => [
                'resolution' => GuarantorDisputeResolutionEnum::PercentageSplit->value,
                'requester_percentage' => 60,
                'notes' => 'split',
            ],
            'expected' => GuarantorStatusEnum::Settled,
        ],
    ];

    foreach ($cases as $case) {
        $admin = createGuarantorDashboardAdmin(['show guarantors', 'manage guarantors']);
        $requester = User::factory()->create();
        $counterparty = User::factory()->create();
        $guarantorRequest = GuarantorRequest::factory()->accepted()->create([
            'requester_id' => $requester->id,
            'requester_type' => User::class,
            'counterparty_id' => $counterparty->id,
            'counterparty_type' => User::class,
            'amount' => 1000,
            'fees' => 10,
        ]);

        $payment = createPaymentFor($counterparty, $guarantorRequest, [
            'amount' => 1010,
            'driver' => 'testing',
            'status' => PaymentStatusEnum::Accepted,
        ]);
        event(new PaymentCompleted($payment->load('product')));

        app(OpenGuarantorDisputeAction::class)->handle(
            $guarantorRequest->fresh(),
            $requester,
            'requester',
            'Dashboard resolve UI test',
        );

        $this->actingAs($admin, 'admin')
            ->from(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
            ->put(
                action([DashboardGuarantorController::class, 'resolveDispute'], $guarantorRequest),
                $case['payload'],
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        expect($guarantorRequest->fresh()->status)->toBe($case['expected']);
    }
});

test('admin can still cancel a disputed guarantor from the dashboard (escape hatch remains available)', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors', 'manage guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->create([
        'status' => GuarantorStatusEnum::Disputed,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->post(action([DashboardGuarantorController::class, 'cancel'], $guarantorRequest), [
            'reason' => 'Admin escape hatch during dispute',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($guarantorRequest->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled);
});

test('Guarantor dashboard resource exposes all 6 media collections (signature, files, authorized_id, contracts, iban_certificates, company_documents) with collection_name intact', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);

    $guarantorRequest = GuarantorRequest::factory()->company()->pendingAdmin()->create();
    $guarantorRequest->addMedia(UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'))
        ->toMediaCollection('signature');
    $guarantorRequest->addMedia(UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf'))
        ->toMediaCollection('files');

    $detail = GuarantorCompanyDetail::query()->create([
        'guarantor_request_id' => $guarantorRequest->id,
        'company_name' => 'Acme',
        'commercial_register' => '123',
        'authorized_name' => 'Auth',
        'authorized_id_number' => '1',
        'authorization_type' => AuthorizationTypeEnum::PowerOfAttorney,
        'requester_account_holder' => 'Holder',
        'requester_iban' => 'SA0380000000608010167519',
        'counterparty_account_holder' => 'CP Holder',
    ]);
    $detail->addMedia(UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'))
        ->toMediaCollection('authorized_id');
    $detail->addMedia(UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'))
        ->toMediaCollection('contracts');
    $detail->addMedia(UploadedFile::fake()->create('iban.pdf', 100, 'application/pdf'))
        ->toMediaCollection('iban_certificates');
    $detail->addMedia(UploadedFile::fake()->create('company-doc.pdf', 100, 'application/pdf'))
        ->toMediaCollection('company_documents');

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Guarantor/Show')
            ->has('guarantorRequest.media', 2)
            ->has('guarantorRequest.company_detail.media', 4)
            ->where('guarantorRequest.media.0.collection_name', fn ($value) => in_array($value, ['signature', 'files'], true))
            ->where('guarantorRequest.media.1.collection_name', fn ($value) => in_array($value, ['signature', 'files'], true))
            ->where(
                'guarantorRequest.company_detail.media',
                fn ($media) => collect($media)->pluck('collection_name')->sort()->values()->all()
                    === ['authorized_id', 'company_documents', 'contracts', 'iban_certificates']
            )
        );
});

test('an admin with show guarantors permission can list conversation messages for a guarantor via the new dashboard endpoint', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create();
    $conversation = app(OpenGuarantorChatAction::class)->handle(
        $guarantorRequest,
        $guarantorRequest->requester,
    );

    $this->actingAs($admin, 'admin')
        ->getJson(action([DashboardGuarantorController::class, 'conversationMessages'], $guarantorRequest))
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($conversation->operation_id)->toBe($guarantorRequest->id);
});

test('an admin with edit/manage guarantors permission can send a conversation message as themselves (not impersonating either party), mirroring AdminInterventionMessenger behavior on Orders', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors', 'manage guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create();
    app(OpenGuarantorChatAction::class)->handle($guarantorRequest, $guarantorRequest->requester);

    $this->actingAs($admin, 'admin')
        ->postJson(action([DashboardGuarantorController::class, 'sendConversationMessage'], $guarantorRequest), [
            'content' => 'Admin reviewing this guarantor chat.',
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.content', 'Admin reviewing this guarantor chat.')
        ->assertJsonPath('data.sender.name', $admin->name);

    expect(
        ConversationMessage::query()
            ->where('conversation_id', $guarantorRequest->fresh()->conversation->id)
            ->whereMorphedTo('sender', $admin)
            ->where('content', 'Admin reviewing this guarantor chat.')
            ->exists()
    )->toBeTrue();
});

test('an admin without the required permission cannot send a message on the guarantor conversation', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create();
    app(OpenGuarantorChatAction::class)->handle($guarantorRequest, $guarantorRequest->requester);

    $this->actingAs($admin, 'admin')
        ->postJson(action([DashboardGuarantorController::class, 'sendConversationMessage'], $guarantorRequest), [
            'content' => 'Should be forbidden',
        ])
        ->assertForbidden();
});

test('the Dispute tab data is derivable entirely from existing status_histories — a guarantor that was disputed and later settled/escalated/ended still shows its dispute history', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors', 'manage guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->create([
        'status' => GuarantorStatusEnum::Disputed,
        'amount' => 1000,
        'fees' => 10,
    ]);
    $guarantorRequest->statusHistories()->create([
        'actor_id' => $guarantorRequest->requester_id,
        'actor_type' => $guarantorRequest->requester_type,
        'from_status' => GuarantorStatusEnum::InProgress->value,
        'to_status' => GuarantorStatusEnum::Disputed->value,
        'reason' => 'Goods not as agreed',
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->put(action([DashboardGuarantorController::class, 'resolveDispute'], $guarantorRequest), [
            'resolution' => GuarantorDisputeResolutionEnum::Escalate->value,
            'notes' => 'escalating after review',
        ])
        ->assertRedirect();

    expect($guarantorRequest->fresh()->status)->toBe(GuarantorStatusEnum::Escalated);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Guarantor/Show')
            ->where('guarantorRequest.status.value', GuarantorStatusEnum::Escalated->value)
            ->where(
                'guarantorRequest.status_histories',
                fn ($histories) => collect($histories)->contains(
                    fn ($history) => ($history['to_status']['value'] ?? null) === GuarantorStatusEnum::Disputed->value
                        && ($history['reason'] ?? null) === 'Goods not as agreed'
                )
            )
            ->where(
                'guarantorRequest.status_histories',
                fn ($histories) => collect($histories)->contains(
                    fn ($history) => ($history['reason'] ?? null) === 'dispute_escalated_to_court'
                )
            )
        );
});
