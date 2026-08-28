<?php

use App\Models\Admin;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Models\Conversation;
use Modules\Guarantor\Actions\Chat\OpenGuarantorChatAction;
use Modules\Guarantor\Actions\Guarantor\AcceptGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CreateCompanyGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CreateIndividualGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\DeleteGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\OpenGuarantorDisputeAction;
use Modules\Guarantor\Actions\Guarantor\RejectGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorStatusAction;
use Modules\Guarantor\Actions\Installment\PayInstallmentAction;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Actions\Payment\AddCounterpartyWalletTransaction;
use Modules\Guarantor\Actions\Payment\AddRequesterWalletTransaction;
use Modules\Guarantor\Actions\Payment\PayIndividualGuarantorAction;
use Modules\Guarantor\Actions\Payment\ProcessGuarantorPayment;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorAcceptUploadData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\GuarantorUploadData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Http\Requests\StoreCompanyGuarantorRequest;
use Modules\Guarantor\Jobs\ReleaseInstallmentJob;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;
use Modules\Guarantor\Notifications\GuarantorAcceptedNotification;
use Modules\Guarantor\Notifications\GuarantorAdminApprovedNotification;
use Modules\Guarantor\Notifications\GuarantorAdminRejectedNotification;
use Modules\Guarantor\Notifications\GuarantorCancelledNotification;
use Modules\Guarantor\Notifications\GuarantorCounterpartyRejectedNotification;
use Modules\Guarantor\Notifications\GuarantorCreatedNotification;
use Modules\Guarantor\Notifications\GuarantorEndedNotification;
use Modules\Guarantor\Notifications\GuarantorPendingReviewNotification;
use Modules\Guarantor\Notifications\InstallmentReleasedNotification;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Spatie\Permission\Models\Permission;

const TEST_COUNTERPARTY_PHONE = '0501234567';

beforeEach(function () {
    Notification::fake();
});

function normalizedCounterpartyPhone(): string
{
    return (string) Phone::make(TEST_COUNTERPARTY_PHONE);
}

/**
 * @return array{requester: User, counterparty: User}
 */
function createGuarantorActors(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create(['phone' => normalizedCounterpartyPhone()]);

    return compact('requester', 'counterparty');
}

function individualGuarantorUploads(): GuarantorUploadData
{
    return GuarantorUploadData::fromIndividualRequest(Request::create('/', 'POST', [], [], [
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
    ]));
}

function companyGuarantorUploads(): GuarantorUploadData
{
    return GuarantorUploadData::fromCompanyRequest(Request::create('/', 'POST', [], [], [
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
        'authorized_id' => UploadedFile::fake()->create('authorized_id.pdf', 100, 'application/pdf'),
        'contracts' => [
            UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ],
        'iban_certificate' => UploadedFile::fake()->create('iban.pdf', 100, 'application/pdf'),
        'cr_file' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf'),
        'articles_of_association' => UploadedFile::fake()->create('aoa.pdf', 100, 'application/pdf'),
        'national_address_file' => UploadedFile::fake()->create('national-address.pdf', 100, 'application/pdf'),
    ]));
}

test('CreateIndividualGuarantorAction creates request and uploads signature', function () {
    ['requester' => $requester, 'counterparty' => $counterparty] = createGuarantorActors();
    Sanctum::actingAs($requester);

    $guarantorRequest = app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Test title',
            description: 'Test description',
            amount: 1000,
            counterparty_phone: TEST_COUNTERPARTY_PHONE,
        ),
        $requester,
        individualGuarantorUploads(),
    );

    expect($guarantorRequest->type)->toBe(GuarantorTypeEnum::Individual)
        ->and($guarantorRequest->status)->toBe(GuarantorStatusEnum::PendingAdmin)
        ->and($guarantorRequest->counterparty_id)->toBe($counterparty->getKey())
        ->and($guarantorRequest->getMedia('requester_signature'))->toHaveCount(1);
});

test('CreateIndividualGuarantorAction fails if counterparty not found', function () {
    $requester = User::factory()->create();
    Sanctum::actingAs($requester);

    app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Test title',
            description: 'Test description',
            amount: 1000,
            counterparty_phone: '0509999999',
        ),
        $requester,
        individualGuarantorUploads(),
    );
})->throws(GuarantorException::class);

test('CreateIndividualGuarantorAction fails if counterparty is same as requester', function () {
    $requester = User::factory()->create(['phone' => normalizedCounterpartyPhone()]);
    Sanctum::actingAs($requester);

    app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Test title',
            description: 'Test description',
            amount: 1000,
            counterparty_phone: TEST_COUNTERPARTY_PHONE,
        ),
        $requester,
        individualGuarantorUploads(),
    );
})->throws(GuarantorException::class);

test('CreateCompanyGuarantorAction creates request with installments and company detail', function () {
    ['requester' => $requester] = createGuarantorActors();
    Sanctum::actingAs($requester);

    $guarantorRequest = app(CreateCompanyGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Company project',
            description: 'Commercial guarantor',
            amount: 1000,
            counterparty_phone: TEST_COUNTERPARTY_PHONE,
            project_type: 'Construction',
        ),
        new CompanyDetailData(
            company_name: 'Acme Corp',
            commercial_register: 'CR-123456',
            region_id: null,
            city_id: null,
            authorized_name: 'John Doe',
            authorized_id_number: '1234567890',
            authorization_type: 'owner',
            requester_account_holder: 'Requester Name',
            requester_iban: 'SA1234567890123456789012',
            requester_bank_id: defaultGuarantorTestBankId(),
            counterparty_account_holder: 'Counterparty Name',
        ),
        [
            new InstallmentData(1, 500, now()->addDays(30)->toDateString()),
            new InstallmentData(2, 500, now()->addDays(60)->toDateString()),
        ],
        $requester,
        companyGuarantorUploads(),
    );

    expect($guarantorRequest->type)->toBe(GuarantorTypeEnum::Company)
        ->and($guarantorRequest->installments)->toHaveCount(2)
        ->and($guarantorRequest->companyDetail)->not->toBeNull()
        ->and($guarantorRequest->companyDetail->company_name)->toBe('Acme Corp')
        ->and($guarantorRequest->getMedia('requester_signature'))->toHaveCount(1);
});

test('CreateCompanyGuarantorAction fails if installments sum != total', function () {
    ['requester' => $requester, 'counterparty' => $counterparty] = createGuarantorActors();
    Sanctum::actingAs($requester);

    $data = [
        'counterparty_phone' => TEST_COUNTERPARTY_PHONE,
        'project_type' => 'Construction',
        'total_amount' => 1000,
        'installments' => [
            ['order' => 1, 'amount' => 400, 'due_date' => now()->addDays(3)->toDateString()],
            ['order' => 2, 'amount' => 400, 'due_date' => now()->addDays(30)->toDateString()],
        ],
        'company_name' => 'Acme Corp',
        'commercial_register' => 'CR-123456',
        'authorized_name' => 'John Doe',
        'authorized_id_number' => '1234567890',
        'authorization_type' => 'owner',
        'requester_account_holder' => 'Requester Name',
        'requester_iban' => 'SA1234567890123456789012',
        'requester_bank_id' => defaultGuarantorTestBankId(),
        'counterparty_account_holder' => 'Counterparty Name',
    ];

    $formRequest = StoreCompanyGuarantorRequest::createFrom(
        Request::create('/', 'POST', $data, [], [
            'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
            'authorized_id' => UploadedFile::fake()->create('authorized_id.pdf', 100, 'application/pdf'),
            'contracts' => [UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf')],
        ])
    );
    $formRequest->setContainer(app());

    $validator = Validator::make($data, $formRequest->rules());
    $formRequest->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('installments'))->toBeTrue();
});

function guarantorActionAdmin(): Admin
{
    return Admin::query()->create([
        'name' => 'Guarantor Action Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
}

/**
 * @param  list<string>  $permissions
 */
function createGuarantorPermissionAdmin(array $permissions): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Guarantor Permission Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $admin->givePermissionTo($permissions);

    return $admin;
}

function companyGuarantorCreateArgs(User $requester): array
{
    return [
        new GuarantorData(
            title: 'Company project',
            description: 'Commercial guarantor',
            amount: 1000,
            counterparty_phone: TEST_COUNTERPARTY_PHONE,
            project_type: 'Construction',
        ),
        new CompanyDetailData(
            company_name: 'Acme Corp',
            commercial_register: 'CR-123456',
            region_id: null,
            city_id: null,
            authorized_name: 'John Doe',
            authorized_id_number: '1234567890',
            authorization_type: 'owner',
            requester_account_holder: 'Requester Name',
            requester_iban: 'SA1234567890123456789012',
            requester_bank_id: defaultGuarantorTestBankId(),
            counterparty_account_holder: 'Counterparty Name',
        ),
        [
            new InstallmentData(1, 500, now()->addDays(30)->toDateString()),
            new InstallmentData(2, 500, now()->addDays(60)->toDateString()),
        ],
        $requester,
        companyGuarantorUploads(),
    ];
}

test('admin can approve pending request', function () {
    Notification::fake();

    $admin = guarantorActionAdmin();
    $guarantorRequest = GuarantorRequest::factory()->pendingAdmin()->create();

    $updated = app(UpdateGuarantorStatusAction::class)->handle(
        $guarantorRequest,
        new UpdateGuarantorStatusData(status: GuarantorStatusEnum::ApprovedByAdmin),
        $admin,
        'admin',
    );

    expect($updated->status)->toBe(GuarantorStatusEnum::ApprovedByAdmin);

    Notification::assertSentTo($guarantorRequest->requester, GuarantorAdminApprovedNotification::class);
    Notification::assertSentTo($guarantorRequest->counterparty, GuarantorAdminApprovedNotification::class);
});

test('admin can reject pending request', function () {
    Notification::fake();

    $admin = guarantorActionAdmin();
    $guarantorRequest = GuarantorRequest::factory()->pendingAdmin()->create();
    $requester = $guarantorRequest->requester;

    $updated = app(UpdateGuarantorStatusAction::class)->handle(
        $guarantorRequest,
        new UpdateGuarantorStatusData(
            status: GuarantorStatusEnum::RejectedByAdmin,
            reason: 'Invalid documents',
        ),
        $admin,
        'admin',
    );

    expect($updated->status)->toBe(GuarantorStatusEnum::RejectedByAdmin)
        ->and($updated->rejected_at)->not->toBeNull();

    Notification::assertSentTo($requester, GuarantorAdminRejectedNotification::class);
});

test('counterparty can accept after admin approval', function () {
    Notification::fake();

    $guarantorRequest = GuarantorRequest::factory()->approvedByAdmin()->create();
    $counterparty = $guarantorRequest->counterparty;
    $requester = $guarantorRequest->requester;

    $updated = app(AcceptGuarantorAction::class)->handle(
        $guarantorRequest,
        $counterparty,
        new GuarantorAcceptUploadData(
            signature: UploadedFile::fake()->create('cp-signature.pdf', 100, 'application/pdf'),
        ),
    );

    expect($updated->status)->toBe(GuarantorStatusEnum::Accepted)
        ->and($updated->getMedia('counterparty_signature'))->toHaveCount(1);

    Notification::assertSentTo($requester, GuarantorAcceptedNotification::class);
});

test('counterparty can reject after admin approval', function () {
    Notification::fake();

    $guarantorRequest = GuarantorRequest::factory()->approvedByAdmin()->create();
    $counterparty = $guarantorRequest->counterparty;
    $requester = $guarantorRequest->requester;

    $updated = app(RejectGuarantorAction::class)->handle(
        $guarantorRequest,
        $counterparty,
        'counterparty',
        'Not acceptable',
    );

    expect($updated->status)->toBe(GuarantorStatusEnum::Rejected)
        ->and($updated->rejected_at)->not->toBeNull();

    Notification::assertSentTo($requester, GuarantorCounterpartyRejectedNotification::class);
});

test('requester cannot accept or reject', function () {
    $guarantorRequest = GuarantorRequest::factory()->approvedByAdmin()->create();
    $requester = $guarantorRequest->requester;

    expect(fn () => app(AcceptGuarantorAction::class)->handle(
        $guarantorRequest,
        $requester,
        new GuarantorAcceptUploadData(
            signature: UploadedFile::fake()->create('sig.pdf', 100, 'application/pdf'),
        ),
    ))->toThrow(GuarantorException::class);

    expect(fn () => app(RejectGuarantorAction::class)->handle(
        $guarantorRequest->fresh(),
        $requester,
        'requester',
        'Nope',
    ))->toThrow(GuarantorException::class);
});

test('chat opens when counterparty accepts', function () {
    $guarantorRequest = GuarantorRequest::factory()->approvedByAdmin()->create();
    $counterparty = $guarantorRequest->counterparty;

    expect(Conversation::query()
        ->where('operation_type', GuarantorRequest::class)
        ->where('operation_id', $guarantorRequest->id)
        ->exists())->toBeFalse();

    app(AcceptGuarantorAction::class)->handle(
        $guarantorRequest,
        $counterparty,
        new GuarantorAcceptUploadData(
            signature: UploadedFile::fake()->create('cp-signature.pdf', 100, 'application/pdf'),
        ),
    );

    expect(Conversation::query()
        ->where('operation_type', GuarantorRequest::class)
        ->where('operation_id', $guarantorRequest->id)
        ->exists())->toBeTrue();
});

test('chat does not open when status is new or pending_admin', function () {
    $pendingAdmin = GuarantorRequest::factory()->pendingAdmin()->create();

    expect(fn () => app(OpenGuarantorChatAction::class)->handle(
        $pendingAdmin,
        $pendingAdmin->requester,
    ))->toThrow(GuarantorException::class);

    expect(Conversation::query()
        ->where('operation_type', GuarantorRequest::class)
        ->where('operation_id', $pendingAdmin->id)
        ->exists())->toBeFalse();
});

test('pay allowed only when accepted', function () {
    config(['app.payment.driver' => 'testing']);

    $approvedByAdmin = GuarantorRequest::factory()->approvedByAdmin()->create(['amount' => 1000, 'fees' => 10]);

    app(PayIndividualGuarantorAction::class)->handle($approvedByAdmin, $approvedByAdmin->counterparty);
})->throws(GuarantorException::class);

test('pay succeeds when status is accepted', function () {
    config(['app.payment.driver' => 'testing']);

    $guarantorRequest = GuarantorRequest::factory()->accepted()->create(['amount' => 1000, 'fees' => 10]);
    $counterparty = $guarantorRequest->counterparty;

    $response = app(PayIndividualGuarantorAction::class)->handle($guarantorRequest, $counterparty);

    expect($response)->toHaveKey('url')
        ->and(Payment::query()->where('product_id', $guarantorRequest->id)->exists())->toBeTrue();
});

test('cannot transition from terminal status', function () {
    $guarantorRequest = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::Rejected]);

    app(UpdateGuarantorStatusAction::class)->handle(
        $guarantorRequest,
        new UpdateGuarantorStatusData(status: GuarantorStatusEnum::Accepted),
        $guarantorRequest->counterparty,
        'counterparty',
    );
})->throws(GuarantorException::class);

test('cannot set same status twice', function () {
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create();
    $counterparty = $guarantorRequest->counterparty;

    app(UpdateGuarantorStatusAction::class)->handle(
        $guarantorRequest,
        new UpdateGuarantorStatusData(status: GuarantorStatusEnum::Accepted),
        $counterparty,
        'counterparty',
    );
})->throws(GuarantorException::class);

test('DeleteGuarantorAction deletes pending_admin request', function () {
    $guarantorRequest = GuarantorRequest::factory()->pendingAdmin()->create();

    app(DeleteGuarantorAction::class)->handle($guarantorRequest);

    expect(GuarantorRequest::withTrashed()->find($guarantorRequest->id)?->trashed())->toBeTrue();
});

test('DeleteGuarantorAction fails for non pending_admin request', function () {
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create();

    app(DeleteGuarantorAction::class)->handle($guarantorRequest);
})->throws(GuarantorException::class);

test('OpenGuarantorChatAction creates conversation when accepted', function () {
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create();

    $conversation = app(OpenGuarantorChatAction::class)->handle($guarantorRequest, $guarantorRequest->requester);

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($conversation->operation_id)->toBe($guarantorRequest->id);
});

test('OpenGuarantorChatAction creates conversation when in_progress', function () {
    $guarantorRequest = GuarantorRequest::factory()->inProgress()->create();

    $conversation = app(OpenGuarantorChatAction::class)->handle($guarantorRequest, $guarantorRequest->requester);

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($conversation->operation_id)->toBe($guarantorRequest->id);
});

test('OpenGuarantorChatAction fails when status is approved by admin', function () {
    $guarantorRequest = GuarantorRequest::factory()->approvedByAdmin()->create();

    app(OpenGuarantorChatAction::class)->handle($guarantorRequest, $guarantorRequest->requester);
})->throws(GuarantorException::class);

test('OpenGuarantorChatAction fails when status is pending_admin', function () {
    $guarantorRequest = GuarantorRequest::factory()->pendingAdmin()->create();

    app(OpenGuarantorChatAction::class)->handle($guarantorRequest, $guarantorRequest->requester);
})->throws(GuarantorException::class);

test('ReleaseInstallmentAction releases installment and updates wallet', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $requester = $guarantorRequest->requester;
    $requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    app(ReleaseInstallmentAction::class)->handle($installment);

    $installment->refresh();
    $requester->wallet->refresh();

    expect($installment->status)->toBe(InstallmentStatusEnum::Released)
        ->and((float) $requester->wallet->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->balance)->toBeGreaterThan(0);
});

test('PayInstallmentAction succeeds when status is accepted', function () {
    config(['app.payment.driver' => 'testing']);

    $guarantorRequest = GuarantorRequest::factory()->company()->accepted()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $response = app(PayInstallmentAction::class)->handle(
        $guarantorRequest,
        $installment,
        $guarantorRequest->counterparty,
    );

    expect($response)->toHaveKey('url')
        ->and(Payment::query()->where('product_id', $installment->id)->exists())->toBeTrue();
});

test('PayInstallmentAction fails if previous installment not paid', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create();
    $first = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'status' => InstallmentStatusEnum::Pending,
    ]);
    $second = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 2,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    app(PayInstallmentAction::class)->handle(
        $guarantorRequest,
        $second,
        $guarantorRequest->counterparty,
    );
})->throws(GuarantorException::class);

test('creating individual guarantor notifies requester', function () {
    Notification::fake();

    ['requester' => $requester] = createGuarantorActors();
    Sanctum::actingAs($requester);

    app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Test title',
            description: 'Test description',
            amount: 1000,
            counterparty_phone: TEST_COUNTERPARTY_PHONE,
        ),
        $requester,
        individualGuarantorUploads(),
    );

    Notification::assertSentTo($requester, GuarantorCreatedNotification::class);
});

test('creating a new individual guarantor request notifies all admins with the correct permission — permission-scoped, not all admins blindly', function () {
    $manageAdmin = createGuarantorPermissionAdmin(['manage guarantors']);
    $otherManageAdmin = createGuarantorPermissionAdmin(['manage guarantors']);
    $viewOnlyAdmin = createGuarantorPermissionAdmin(['show guarantors']);

    ['requester' => $requester] = createGuarantorActors();
    Sanctum::actingAs($requester);

    app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Test title',
            description: 'Test description',
            amount: 1000,
            counterparty_phone: TEST_COUNTERPARTY_PHONE,
        ),
        $requester,
        individualGuarantorUploads(),
    );

    Notification::assertSentTo($manageAdmin, GuarantorPendingReviewNotification::class);
    Notification::assertSentTo($otherManageAdmin, GuarantorPendingReviewNotification::class);
    Notification::assertNotSentTo($viewOnlyAdmin, GuarantorPendingReviewNotification::class);
});

test('creating a new company guarantor request notifies all admins with the correct permission', function () {
    $manageAdmin = createGuarantorPermissionAdmin(['manage guarantors']);
    $viewOnlyAdmin = createGuarantorPermissionAdmin(['show guarantors']);

    ['requester' => $requester] = createGuarantorActors();
    Sanctum::actingAs($requester);

    app(CreateCompanyGuarantorAction::class)->handle(
        ...companyGuarantorCreateArgs($requester),
    );

    Notification::assertSentTo($manageAdmin, GuarantorPendingReviewNotification::class);
    Notification::assertNotSentTo($viewOnlyAdmin, GuarantorPendingReviewNotification::class);
});

test('admin cancelling a guarantor request sends a dedicated Cancelled notification to both parties, not the Ended notification', function () {
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create();
    $requester = $guarantorRequest->requester;
    $counterparty = $guarantorRequest->counterparty;
    $admin = guarantorActionAdmin();

    app(CancelGuarantorAction::class)->handle(
        $guarantorRequest,
        'Client withdrew from the contract',
        null,
        $admin,
    );

    Notification::assertSentTo($requester, GuarantorCancelledNotification::class);
    Notification::assertSentTo($counterparty, GuarantorCancelledNotification::class);
    Notification::assertNotSentTo($requester, GuarantorEndedNotification::class);
    Notification::assertNotSentTo($counterparty, GuarantorEndedNotification::class);
});

test('ending a guarantor request still sends the existing Ended notification unchanged — no regression on the end path', function () {
    $guarantorRequest = GuarantorRequest::factory()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $requester = $guarantorRequest->requester;
    $counterparty = $guarantorRequest->counterparty;

    $requester->wallet->update(['pending_credit' => 1010]);
    $counterparty->wallet->update(['pending_debit' => 1010]);

    app(EndGuarantorAction::class)->handle(
        $guarantorRequest,
        $requester,
        'requester',
    );

    Notification::assertSentTo($requester, GuarantorEndedNotification::class);
    Notification::assertSentTo($counterparty, GuarantorEndedNotification::class);
    Notification::assertNotSentTo($requester, GuarantorCancelledNotification::class);
    Notification::assertNotSentTo($counterparty, GuarantorCancelledNotification::class);
});

test('ending guarantor notifies both parties', function () {
    Notification::fake();

    $guarantorRequest = GuarantorRequest::factory()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $requester = $guarantorRequest->requester;
    $counterparty = $guarantorRequest->counterparty;

    $requester->wallet->update(['pending_credit' => 1010]);
    $counterparty->wallet->update(['pending_debit' => 1010]);

    app(EndGuarantorAction::class)->handle(
        $guarantorRequest,
        $requester,
        'requester',
    );

    Notification::assertSentTo($requester, GuarantorEndedNotification::class);
    Notification::assertSentTo($counterparty, GuarantorEndedNotification::class);
});

test('releasing installment notifies requester', function () {
    Notification::fake();

    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $requester = $guarantorRequest->requester;
    $requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    app(ReleaseInstallmentAction::class)->handle($installment);

    Notification::assertSentTo($requester, InstallmentReleasedNotification::class);
});

function acceptedGuarantorPayment(GuarantorRequest $request, User $payer, float $amount): Payment
{
    $payment = Payment::query()->create([
        'user_id' => $payer->getKey(),
        'user_type' => User::class,
        'product_id' => $request->id,
        'product_type' => GuarantorRequest::class,
        'amount' => $amount,
        'status' => PaymentStatusEnum::Accepted,
        'driver' => 'testing',
    ]);

    return $payment->load('product');
}

function acceptedInstallmentPayment(GuarantorInstallment $installment, User $payer): Payment
{
    $payment = Payment::query()->create([
        'user_id' => $payer->getKey(),
        'user_type' => User::class,
        'product_id' => $installment->id,
        'product_type' => GuarantorInstallment::class,
        'amount' => $installment->amount,
        'status' => PaymentStatusEnum::Accepted,
        'driver' => 'testing',
    ]);

    return $payment->load('product');
}

function runPaymentPipelineStage(object $stage, Payment $payment): Payment
{
    return $stage($payment, fn (Payment $passed) => $passed);
}

test('PayIndividualGuarantorAction creates payment and returns gateway url', function () {
    config(['app.payment.driver' => 'testing']);

    $guarantorRequest = GuarantorRequest::factory()->accepted()->create(['amount' => 1000, 'fees' => 10]);
    $counterparty = $guarantorRequest->counterparty;

    $response = app(PayIndividualGuarantorAction::class)->handle($guarantorRequest, $counterparty);

    expect($response)->toHaveKey('url')
        ->and(Payment::query()->where('product_id', $guarantorRequest->id)->exists())->toBeTrue();
});

test('ProcessGuarantorPayment sets request to in_progress on payment accepted', function () {
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create(['amount' => 1000, 'fees' => 10]);
    $payment = acceptedGuarantorPayment($guarantorRequest, $guarantorRequest->counterparty, 1010);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    expect($guarantorRequest->fresh()->status)->toBe(GuarantorStatusEnum::InProgress);
});

test('payment processing does not open chat', function () {
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create(['amount' => 1000, 'fees' => 10]);
    $payment = acceptedGuarantorPayment($guarantorRequest, $guarantorRequest->counterparty, 1010);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    expect(Conversation::query()
        ->where('operation_type', GuarantorRequest::class)
        ->where('operation_id', $guarantorRequest->id)
        ->exists())->toBeFalse();
});

test('installment overdue recovery does not open chat', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->create([
        'status' => GuarantorStatusEnum::Overdue,
        'overdue_at' => now(),
        'amount' => 1000,
        'fees' => 10,
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $payment = acceptedInstallmentPayment($installment, $guarantorRequest->counterparty);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    expect($guarantorRequest->fresh()->status)->toBe(GuarantorStatusEnum::InProgress)
        ->and(Conversation::query()
            ->where('operation_type', GuarantorRequest::class)
            ->where('operation_id', $guarantorRequest->id)
            ->exists())->toBeFalse();
});

test('a Company guarantor transitions from accepted to in_progress when its first installment payment completes', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->accepted()->create([
        'amount' => 1000,
        'fees' => 10,
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $payment = acceptedInstallmentPayment($installment, $guarantorRequest->counterparty);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    expect($guarantorRequest->fresh()->status)->toBe(GuarantorStatusEnum::InProgress)
        ->and($installment->fresh()->status)->toBe(InstallmentStatusEnum::Paid);
});

test('the existing Overdue -> InProgress recovery on installment payment still works unchanged — regression', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->create([
        'status' => GuarantorStatusEnum::Overdue,
        'overdue_at' => now(),
        'amount' => 1000,
        'fees' => 10,
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $payment = acceptedInstallmentPayment($installment, $guarantorRequest->counterparty);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    $fresh = $guarantorRequest->fresh();

    expect($fresh->status)->toBe(GuarantorStatusEnum::InProgress)
        ->and($fresh->overdue_at)->toBeNull()
        ->and($installment->fresh()->status)->toBe(InstallmentStatusEnum::Paid);
});

test('a Company guarantor already in_progress stays in_progress on subsequent installment payments (no duplicate/incorrect history entries)', function () {
    Queue::fake();

    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create([
        'amount' => 1000,
        'fees' => 10,
    ]);
    GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $second = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 500,
    ]);

    $historyCountBefore = GuarantorStatusHistory::query()
        ->where('guarantor_request_id', $guarantorRequest->id)
        ->count();

    $payment = acceptedInstallmentPayment($second, $guarantorRequest->counterparty);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    expect($guarantorRequest->fresh()->status)->toBe(GuarantorStatusEnum::InProgress)
        ->and(GuarantorStatusHistory::query()
            ->where('guarantor_request_id', $guarantorRequest->id)
            ->count())->toBe($historyCountBefore)
        ->and(GuarantorStatusHistory::query()
            ->where('guarantor_request_id', $guarantorRequest->id)
            ->where('from_status', GuarantorStatusEnum::InProgress->value)
            ->where('to_status', GuarantorStatusEnum::InProgress->value)
            ->exists())->toBeFalse();
});

test('the status transition is logged via LogGuarantorStatusHistoryAction with the correct from/to values', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->accepted()->create([
        'amount' => 1000,
        'fees' => 10,
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $payment = acceptedInstallmentPayment($installment, $guarantorRequest->counterparty);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    $history = GuarantorStatusHistory::query()
        ->where('guarantor_request_id', $guarantorRequest->id)
        ->where('to_status', GuarantorStatusEnum::InProgress->value)
        ->latest()
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->from_status)->toBe(GuarantorStatusEnum::Accepted->value)
        ->and($history->to_status)->toBe(GuarantorStatusEnum::InProgress->value)
        ->and($history->notes)->toBe('Payment accepted by gateway');
});

test('End is now reachable for a Company guarantor after its first installment payment, without requiring it to first go overdue', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->accepted()->create([
        'amount' => 1000,
        'fees' => 10,
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $payment = acceptedInstallmentPayment($installment, $guarantorRequest->counterparty);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    $ended = app(EndGuarantorAction::class)->handle(
        $guarantorRequest->fresh(),
        $guarantorRequest->requester,
        'requester',
    );

    expect($ended->status)->toBe(GuarantorStatusEnum::Ended);
});

test('opening a dispute is now reachable for a Company guarantor after its first installment payment, without requiring it to first go overdue', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->accepted()->create([
        'amount' => 1000,
        'fees' => 10,
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $payment = acceptedInstallmentPayment($installment, $guarantorRequest->counterparty);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    $disputed = app(OpenGuarantorDisputeAction::class)->handle(
        $guarantorRequest->fresh(),
        $guarantorRequest->requester,
        'requester',
        'Goods not as agreed',
    );

    expect($disputed->status)->toBe(GuarantorStatusEnum::Disputed);
});

test('ProcessGuarantorPayment sets installment to paid on installment payment', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $payment = acceptedInstallmentPayment($installment, $guarantorRequest->counterparty);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    $installment->refresh();

    expect($installment->status)->toBe(InstallmentStatusEnum::Paid)
        ->and($installment->paid_at)->not->toBeNull();
});

test('ProcessGuarantorPayment dispatches ReleaseInstallmentJob for previous installment', function () {
    Queue::fake();

    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $first = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $second = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 500,
    ]);

    $payment = acceptedInstallmentPayment($second, $guarantorRequest->counterparty);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    Queue::assertPushed(ReleaseInstallmentJob::class, fn (ReleaseInstallmentJob $job) => $job->installment->is($first));
});

test('AddCounterpartyWalletTransaction increments pending_credit on requester wallet', function () {
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create(['amount' => 1000, 'fees' => 10]);
    $payment = acceptedGuarantorPayment($guarantorRequest, $guarantorRequest->counterparty, 1010);
    $requester = $guarantorRequest->requester;
    $requester->wallet->update(['pending_credit' => 0, 'balance' => 0]);

    runPaymentPipelineStage(app(AddCounterpartyWalletTransaction::class), $payment);

    expect((float) $requester->wallet->fresh()->pending_credit)->toBe(1010.0);
});

test('AddRequesterWalletTransaction increments pending_debit on counterparty wallet', function () {
    $guarantorRequest = GuarantorRequest::factory()->accepted()->create(['amount' => 1000, 'fees' => 10]);
    $payment = acceptedGuarantorPayment($guarantorRequest, $guarantorRequest->counterparty, 1010);
    $counterparty = $guarantorRequest->counterparty;
    $counterparty->wallet->update(['pending_debit' => 0, 'balance' => 0]);

    runPaymentPipelineStage(app(AddRequesterWalletTransaction::class), $payment);

    expect((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1010.0);
});

test('ReleaseInstallmentJob releases installment and updates wallet', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $requester = $guarantorRequest->requester;
    $requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    (new ReleaseInstallmentJob($installment, 'payment'))
        ->handle(app(ReleaseInstallmentAction::class));

    $installment->refresh();
    $requester->wallet->refresh();

    expect($installment->status)->toBe(InstallmentStatusEnum::Released)
        ->and((float) $requester->wallet->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->balance)->toBeGreaterThan(0);
});
