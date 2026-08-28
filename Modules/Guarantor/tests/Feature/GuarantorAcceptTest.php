<?php

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Models\Conversation;
use Modules\Guarantor\Actions\Guarantor\CreateCompanyGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CreateIndividualGuarantorAction;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\GuarantorUploadData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorAcceptedNotification;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest}
 */
function acceptContext(array $requestAttributes = []): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();
    $request = GuarantorRequest::factory()->create(array_merge([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'amount' => 1000,
        'fees' => 10,
        'status' => GuarantorStatusEnum::ApprovedByAdmin,
    ], $requestAttributes));

    return compact('requester', 'counterparty', 'request');
}

function acceptSignatureFile(): UploadedFile
{
    return UploadedFile::fake()->create('cp-signature.pdf', 100, 'application/pdf');
}

test('requester signature is still written to the renamed requester_signature collection at create, Individual and Company', function () {
    $requester = User::factory()->create();
    $counterparty = User::factory()->create([
        'phone' => (string) Phone::make('0501112233'),
    ]);
    Sanctum::actingAs($requester);

    $individual = app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Individual accept test',
            description: 'Desc',
            amount: 1000,
            counterparty_phone: '0501112233',
        ),
        $requester,
        new GuarantorUploadData(signature: acceptSignatureFile()),
    );

    expect($individual->getMedia('requester_signature'))->toHaveCount(1)
        ->and($individual->getMedia('signature'))->toHaveCount(0);

    $company = app(CreateCompanyGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Company accept test',
            description: '',
            amount: 1000,
            counterparty_phone: '0501112233',
            project_type: 'Construction',
        ),
        new CompanyDetailData(
            company_name: 'Acme',
            commercial_register: 'CR-1',
            region_id: null,
            city_id: null,
            authorized_name: 'Auth',
            authorized_id_number: '1',
            authorization_type: 'power_of_attorney',
            requester_account_holder: 'Holder',
            requester_iban: 'SA1234567890123456789012',
            requester_bank_id: defaultGuarantorTestBankId(),
            counterparty_account_holder: 'CP',
        ),
        [
            new InstallmentData(1, 500, now()->addDays(30)->toDateString()),
            new InstallmentData(2, 500, now()->addDays(60)->toDateString()),
        ],
        $requester,
        new GuarantorUploadData(
            signature: acceptSignatureFile(),
            authorizedId: UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
            contracts: [UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf')],
        ),
    );

    expect($company->getMedia('requester_signature'))->toHaveCount(1)
        ->and($company->getMedia('signature'))->toHaveCount(0);
});

test('existing tests referencing the old signature collection name are updated to requester_signature — no stale references', function () {
    $staleHits = [];

    $paths = [
        base_path('Modules/Guarantor/Models/GuarantorRequest.php'),
        base_path('Modules/Guarantor/Actions/Guarantor/CreateIndividualGuarantorAction.php'),
        base_path('Modules/Guarantor/Actions/Guarantor/CreateCompanyGuarantorAction.php'),
        base_path('Modules/Guarantor/tests/Feature/CoverageTest.php'),
        base_path('Modules/Guarantor/tests/Feature/DashboardTest.php'),
        base_path('Modules/Guarantor/tests/Feature/GuarantorActionTest.php'),
        base_path('Modules/Guarantor/tests/Feature/GuarantorWebpMediaConversionTest.php'),
        base_path('resources/js/apps/admin/pages/Guarantor/components/documents-tab.tsx'),
    ];

    foreach ($paths as $path) {
        $contents = file_get_contents($path);
        if ($contents === false) {
            continue;
        }

        if (preg_match("/toMediaCollection\\(['\"]signature['\"]\\)/", $contents)
            || preg_match("/getMedia\\(['\"]signature['\"]\\)/", $contents)
            || preg_match("/addMediaCollection\\(['\"]signature['\"]\\)/", $contents)
            || preg_match("/collection_name['\"]\\s*=>\\s*['\"]signature['\"]/", $contents)
            || preg_match("/key:\\s*['\"]signature['\"]/", $contents)
        ) {
            $staleHits[] = $path;
        }
    }

    expect($staleHits)->toBe([]);
});

test('POST /guarantor/{id}/accept requires a signature file — request without one is rejected with a clear validation error', function () {
    ['counterparty' => $counterparty, 'request' => $request] = acceptContext();

    Sanctum::actingAs($counterparty);

    $this->post(route('api.v1.guarantor.guarantor.accept', $request), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['signature']);
});

test('POST /guarantor/{id}/accept succeeds for the counterparty from approved_by_admin, storing the file in counterparty_signature and transitioning status to accepted', function () {
    ['counterparty' => $counterparty, 'request' => $request] = acceptContext();

    Sanctum::actingAs($counterparty);

    $this->post(route('api.v1.guarantor.guarantor.accept', $request), [
        'signature' => acceptSignatureFile(),
    ])->assertSuccessful()
        ->assertJsonPath('data.status.value', GuarantorStatusEnum::Accepted->value);

    $request->refresh();

    expect($request->status)->toBe(GuarantorStatusEnum::Accepted)
        ->and($request->getMedia('counterparty_signature'))->toHaveCount(1);
});

test('POST /guarantor/{id}/accept is rejected for the requester (not their action to take)', function () {
    ['requester' => $requester, 'request' => $request] = acceptContext();

    Sanctum::actingAs($requester);

    $this->post(route('api.v1.guarantor.guarantor.accept', $request), [
        'signature' => acceptSignatureFile(),
    ])->assertForbidden();
});

test('POST /guarantor/{id}/accept is rejected from any status other than approved_by_admin', function () {
    ['counterparty' => $counterparty, 'request' => $request] = acceptContext([
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);

    Sanctum::actingAs($counterparty);

    $this->post(route('api.v1.guarantor.guarantor.accept', $request), [
        'signature' => acceptSignatureFile(),
    ])->assertForbidden();
});

test('POST /guarantor/{id}/status no longer exists — accept must go through /accept', function () {
    ['counterparty' => $counterparty, 'request' => $request] = acceptContext();

    Sanctum::actingAs($counterparty);

    $this->postJson('/api/v1/guarantor/'.$request->id.'/status', [
        'status' => GuarantorStatusEnum::Accepted->value,
    ])->assertNotFound();
});

test('a re-accept attempt (already accepted) via /accept is rejected cleanly, not a raw exception', function () {
    ['counterparty' => $counterparty, 'request' => $request] = acceptContext([
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    Sanctum::actingAs($counterparty);

    $this->post(route('api.v1.guarantor.guarantor.accept', $request), [
        'signature' => acceptSignatureFile(),
    ])->assertForbidden();
});

test('chat still opens and GuarantorAcceptedNotification still fires correctly via the new accept flow — regression against existing accept side-effects', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = acceptContext();

    Sanctum::actingAs($counterparty);

    $this->post(route('api.v1.guarantor.guarantor.accept', $request), [
        'signature' => acceptSignatureFile(),
    ])->assertSuccessful();

    expect(Conversation::query()
        ->where('operation_type', GuarantorRequest::class)
        ->where('operation_id', $request->id)
        ->exists())->toBeTrue();

    Notification::assertSentTo($requester, GuarantorAcceptedNotification::class);
});

test('counterparty_signature is excluded from WebP conversion, matching requester_signature (legal document integrity)', function () {
    config(['media-library.queue_conversions_after_database_commit' => false]);
    Storage::fake('public');
    Bus::fake();

    $guarantor = GuarantorRequest::factory()->create();
    $guarantor->addMedia(UploadedFile::fake()->image('cp.png', 20, 20))
        ->toMediaCollection('counterparty_signature', 'public');

    Bus::assertNotDispatched(
        PerformConversionsJob::class
    );
});
