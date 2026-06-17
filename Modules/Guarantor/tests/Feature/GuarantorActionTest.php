<?php

use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Conversation;
use App\Models\Payment;
use App\Models\User;
use App\Services\Sms\Phone;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Actions\Chat\OpenGuarantorChatAction;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CreateCompanyGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CreateIndividualGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\DeleteGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorStatusAction;
use Modules\Guarantor\Actions\Installment\PayInstallmentAction;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Actions\Payment\AddCounterpartyWalletTransaction;
use Modules\Guarantor\Actions\Payment\AddRequesterWalletTransaction;
use Modules\Guarantor\Actions\Payment\PayIndividualGuarantorAction;
use Modules\Guarantor\Actions\Payment\ProcessGuarantorPayment;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorData;
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
use Modules\Guarantor\Notifications\GuarantorApprovedNotification;
use Modules\Guarantor\Notifications\GuarantorCreatedNotification;
use Modules\Guarantor\Notifications\GuarantorEndedNotification;
use Modules\Guarantor\Notifications\GuarantorRejectedNotification;
use Modules\Guarantor\Notifications\InstallmentReleasedNotification;
use Modules\Guarantor\Services\GuarantorService;

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

function individualGuarantorHttpRequest(): Request
{
    return Request::create('/', 'POST', [], [], [
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
    ]);
}

function companyGuarantorHttpRequest(): Request
{
    return Request::create('/', 'POST', [], [], [
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
        'authorized_id' => UploadedFile::fake()->create('authorized_id.pdf', 100, 'application/pdf'),
        'contracts' => [
            UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ],
    ]);
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
        individualGuarantorHttpRequest(),
    );

    expect($guarantorRequest->type)->toBe(GuarantorTypeEnum::Individual)
        ->and($guarantorRequest->status)->toBe(GuarantorStatusEnum::New)
        ->and($guarantorRequest->counterparty_id)->toBe($counterparty->getKey())
        ->and($guarantorRequest->getMedia('signature'))->toHaveCount(1);
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
        individualGuarantorHttpRequest(),
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
        individualGuarantorHttpRequest(),
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
            authorization_type: 'power_of_attorney',
            requester_account_holder: 'Requester Name',
            requester_iban: 'SA1234567890123456789012',
            counterparty_account_holder: 'Counterparty Name',
        ),
        [
            new InstallmentData(1, 500, now()->addDays(30)->toDateString()),
            new InstallmentData(2, 500, now()->addDays(60)->toDateString()),
        ],
        $requester,
        companyGuarantorHttpRequest(),
    );

    expect($guarantorRequest->type)->toBe(GuarantorTypeEnum::Company)
        ->and($guarantorRequest->installments)->toHaveCount(2)
        ->and($guarantorRequest->companyDetail)->not->toBeNull()
        ->and($guarantorRequest->companyDetail->company_name)->toBe('Acme Corp')
        ->and($guarantorRequest->getMedia('signature'))->toHaveCount(1);
});

test('CreateCompanyGuarantorAction fails if installments sum != total', function () {
    ['requester' => $requester, 'counterparty' => $counterparty] = createGuarantorActors();
    Sanctum::actingAs($requester);

    $data = [
        'counterparty_phone' => TEST_COUNTERPARTY_PHONE,
        'project_type' => 'Construction',
        'total_amount' => 1000,
        'installments' => [
            ['order' => 1, 'amount' => 400, 'due_date' => now()->addDays(30)->toDateString()],
            ['order' => 2, 'amount' => 400, 'due_date' => now()->addDays(60)->toDateString()],
        ],
        'company_name' => 'Acme Corp',
        'commercial_register' => 'CR-123456',
        'authorized_name' => 'John Doe',
        'authorized_id_number' => '1234567890',
        'authorization_type' => 'power_of_attorney',
        'requester_account_holder' => 'Requester Name',
        'requester_iban' => 'SA1234567890123456789012',
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

test('UpdateGuarantorStatusAction approves request and opens chat', function () {
    $guarantorRequest = GuarantorRequest::factory()->create([
        'status' => GuarantorStatusEnum::New,
    ]);
    $counterparty = $guarantorRequest->counterparty;

    $updated = app(UpdateGuarantorStatusAction::class)->handle(
        $guarantorRequest,
        new UpdateGuarantorStatusData(status: GuarantorStatusEnum::Approved),
        $counterparty,
        'counterparty',
    );

    expect($updated->status)->toBe(GuarantorStatusEnum::Approved);

    expect(Conversation::query()
        ->where('operation_type', GuarantorRequest::class)
        ->where('operation_id', $guarantorRequest->id)
        ->exists())->toBeTrue();
});

test('UpdateGuarantorStatusAction rejects request', function () {
    $guarantorRequest = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::New]);
    $counterparty = $guarantorRequest->counterparty;

    $updated = app(UpdateGuarantorStatusAction::class)->handle(
        $guarantorRequest,
        new UpdateGuarantorStatusData(
            status: GuarantorStatusEnum::Rejected,
            reason: 'Not acceptable',
        ),
        $counterparty,
        'counterparty',
    );

    expect($updated->status)->toBe(GuarantorStatusEnum::Rejected);
});

test('UpdateGuarantorStatusAction fails for invalid transition', function () {
    $guarantorRequest = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::New]);

    app(UpdateGuarantorStatusAction::class)->handle(
        $guarantorRequest,
        new UpdateGuarantorStatusData(status: GuarantorStatusEnum::Approved),
        $guarantorRequest->requester,
        'requester',
    );
})->throws(GuarantorException::class);

test('requester cannot approve', function () {
    $guarantorRequest = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::New]);
    $requester = $guarantorRequest->requester;
    $service = app(GuarantorService::class);

    $service->updateStatus(
        $guarantorRequest,
        new UpdateGuarantorStatusData(status: GuarantorStatusEnum::Approved),
        $requester,
        $service->resolveActorRole($guarantorRequest, $requester),
    );
})->throws(GuarantorException::class);

test('cannot set same status twice', function () {
    $guarantorRequest = GuarantorRequest::factory()->approved()->create();
    $counterparty = $guarantorRequest->counterparty;

    app(UpdateGuarantorStatusAction::class)->handle(
        $guarantorRequest,
        new UpdateGuarantorStatusData(status: GuarantorStatusEnum::Approved),
        $counterparty,
        'counterparty',
    );
})->throws(GuarantorException::class);

test('DeleteGuarantorAction deletes new request', function () {
    $guarantorRequest = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::New]);

    app(DeleteGuarantorAction::class)->handle($guarantorRequest);

    expect(GuarantorRequest::withTrashed()->find($guarantorRequest->id)?->trashed())->toBeTrue();
});

test('DeleteGuarantorAction fails for non-new request', function () {
    $guarantorRequest = GuarantorRequest::factory()->approved()->create();

    app(DeleteGuarantorAction::class)->handle($guarantorRequest);
})->throws(GuarantorException::class);

test('OpenGuarantorChatAction creates conversation on approve', function () {
    $guarantorRequest = GuarantorRequest::factory()->approved()->create();

    $conversation = app(OpenGuarantorChatAction::class)->handle($guarantorRequest);

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($conversation->operation_id)->toBe($guarantorRequest->id);
});

test('OpenGuarantorChatAction fails when status is new', function () {
    $guarantorRequest = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::New]);

    app(OpenGuarantorChatAction::class)->handle($guarantorRequest);
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

test('CancelGuarantorAction reverses wallet on cancel after payment', function () {
    $guarantorRequest = GuarantorRequest::factory()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $requester = $guarantorRequest->requester;
    $counterparty = $guarantorRequest->counterparty;

    $requester->wallet->update(['pending_credit' => 1010]);
    $counterparty->wallet->update(['pending_debit' => 1010]);

    app(CancelGuarantorAction::class)->handle(
        $guarantorRequest,
        'Changed plans',
        $requester,
        'requester',
    );

    $requester->wallet->refresh();
    $counterparty->wallet->refresh();

    expect($guarantorRequest->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled)
        ->and((float) $requester->wallet->pending_credit)->toBe(0.0)
        ->and((float) $counterparty->wallet->pending_debit)->toBe(0.0);
});

test('creating individual guarantor notifies counterparty', function () {
    Notification::fake();

    ['requester' => $requester, 'counterparty' => $counterparty] = createGuarantorActors();
    Sanctum::actingAs($requester);

    app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Test title',
            description: 'Test description',
            amount: 1000,
            counterparty_phone: TEST_COUNTERPARTY_PHONE,
        ),
        $requester,
        individualGuarantorHttpRequest(),
    );

    Notification::assertSentTo($counterparty, GuarantorCreatedNotification::class);
});

test('approving guarantor notifies requester', function () {
    Notification::fake();

    $guarantorRequest = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::New]);
    $counterparty = $guarantorRequest->counterparty;
    $requester = $guarantorRequest->requester;

    app(UpdateGuarantorStatusAction::class)->handle(
        $guarantorRequest,
        new UpdateGuarantorStatusData(status: GuarantorStatusEnum::Approved),
        $counterparty,
        'counterparty',
    );

    Notification::assertSentTo($requester, GuarantorApprovedNotification::class);
});

test('rejecting guarantor notifies requester', function () {
    Notification::fake();

    $guarantorRequest = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::New]);
    $counterparty = $guarantorRequest->counterparty;
    $requester = $guarantorRequest->requester;

    app(UpdateGuarantorStatusAction::class)->handle(
        $guarantorRequest,
        new UpdateGuarantorStatusData(
            status: GuarantorStatusEnum::Rejected,
            reason: 'Not acceptable',
        ),
        $counterparty,
        'counterparty',
    );

    Notification::assertSentTo($requester, GuarantorRejectedNotification::class);
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

    $guarantorRequest = GuarantorRequest::factory()->approved()->create(['amount' => 1000, 'fees' => 10]);
    $counterparty = $guarantorRequest->counterparty;

    $response = app(PayIndividualGuarantorAction::class)->handle($guarantorRequest, $counterparty);

    expect($response)->toHaveKey('url')
        ->and(Payment::query()->where('product_id', $guarantorRequest->id)->exists())->toBeTrue();
});

test('ProcessGuarantorPayment sets request to in_progress on payment accepted', function () {
    $guarantorRequest = GuarantorRequest::factory()->approved()->create(['amount' => 1000, 'fees' => 10]);
    $payment = acceptedGuarantorPayment($guarantorRequest, $guarantorRequest->counterparty, 1010);

    runPaymentPipelineStage(app(ProcessGuarantorPayment::class), $payment);

    expect($guarantorRequest->fresh()->status)->toBe(GuarantorStatusEnum::InProgress);
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
    $guarantorRequest = GuarantorRequest::factory()->approved()->create(['amount' => 1000, 'fees' => 10]);
    $payment = acceptedGuarantorPayment($guarantorRequest, $guarantorRequest->counterparty, 1010);
    $requester = $guarantorRequest->requester;
    $requester->wallet->update(['pending_credit' => 0, 'balance' => 0]);

    runPaymentPipelineStage(app(AddCounterpartyWalletTransaction::class), $payment);

    expect((float) $requester->wallet->fresh()->pending_credit)->toBe(1010.0);
});

test('AddRequesterWalletTransaction increments pending_debit on counterparty wallet', function () {
    $guarantorRequest = GuarantorRequest::factory()->approved()->create(['amount' => 1000, 'fees' => 10]);
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
