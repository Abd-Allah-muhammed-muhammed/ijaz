<?php

use App\Http\Resources\Api\V1\MediaResource;
use App\Models\User;
use App\Services\Sms\Phone;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Modules\Guarantor\Actions\Guarantor\CreateCompanyGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CreateIndividualGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\DeleteGuarantorMediaAction;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorStatusAction;
use Modules\Guarantor\Actions\Payment\PayIndividualGuarantorAction;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Http\Resources\Api\GuarantorResource;
use Modules\Guarantor\Models\GuarantorRequest;

const COVERAGE_COUNTERPARTY_PHONE = '0507654321';

beforeEach(function () {
    Notification::fake();
});

function coverageCounterpartyPhone(): string
{
    return (string) Phone::make(COVERAGE_COUNTERPARTY_PHONE);
}

/**
 * @return array{requester: User, counterparty: User}
 */
function coverageGuarantorActors(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create(['phone' => coverageCounterpartyPhone()]);

    return compact('requester', 'counterparty');
}

function coverageIndividualRequest(): Request
{
    return Request::create('/', 'POST', [], [], [
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
    ]);
}

function coverageCompanyRequest(): Request
{
    return Request::create('/', 'POST', [], [], [
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
        'authorized_id' => UploadedFile::fake()->create('authorized_id.pdf', 100, 'application/pdf'),
        'contracts' => [
            UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ],
    ]);
}

test('individual create resource includes uploaded media', function () {
    ['requester' => $requester] = coverageGuarantorActors();

    $guarantorRequest = app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Media test',
            description: 'Media test description',
            amount: 1000,
            counterparty_phone: COVERAGE_COUNTERPARTY_PHONE,
        ),
        $requester,
        coverageIndividualRequest(),
    );

    $guarantorRequest->load('media');

    expect($guarantorRequest->getMedia('files'))->toHaveCount(1);

    $request = Request::create('/');
    $resource = GuarantorResource::make($guarantorRequest)->toArray($request);
    $mediaData = MediaResource::collection($guarantorRequest->media)->toArray($request);

    expect($resource)->toHaveKey('media')
        ->and($mediaData)->toHaveCount(1)
        ->and($mediaData[0])->toHaveKeys(['id', 'url', 'mime_type']);
});

test('updateStatus from terminal status throws exception', function () {
    $guarantorRequest = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::Ended]);

    app(UpdateGuarantorStatusAction::class)->handle(
        $guarantorRequest,
        new UpdateGuarantorStatusData(status: GuarantorStatusEnum::Cancelled, reason: 'Too late'),
        $guarantorRequest->requester,
        'requester',
    );
})->throws(GuarantorException::class);

test('DeleteGuarantorMediaAction fails when status is not new', function () {
    $guarantorRequest = GuarantorRequest::factory()->approved()->create();
    $media = $guarantorRequest
        ->addMedia(UploadedFile::fake()->create('file.pdf', 100, 'application/pdf'))
        ->toMediaCollection('files');

    app(DeleteGuarantorMediaAction::class)->handle($guarantorRequest, $media);
})->throws(GuarantorException::class);

test('PayIndividualGuarantorAction fails when status is not approved', function () {
    $guarantorRequest = GuarantorRequest::factory()->inProgress()->create();

    app(PayIndividualGuarantorAction::class)->handle(
        $guarantorRequest,
        $guarantorRequest->counterparty,
    );
})->throws(GuarantorException::class);

test('company create persists installments with correct order and amounts', function () {
    ['requester' => $requester] = coverageGuarantorActors();

    $guarantorRequest = app(CreateCompanyGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Company installments',
            description: 'Company description',
            amount: 1000,
            counterparty_phone: COVERAGE_COUNTERPARTY_PHONE,
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
            new InstallmentData(1, 600, now()->addDays(30)->toDateString()),
            new InstallmentData(2, 400, now()->addDays(60)->toDateString()),
        ],
        $requester,
        coverageCompanyRequest(),
    );

    $installments = $guarantorRequest->installments()->orderBy('order')->get();

    expect($installments)->toHaveCount(2)
        ->and((float) $installments[0]->amount)->toBe(600.0)
        ->and((float) $installments[1]->amount)->toBe(400.0)
        ->and($installments[0]->order)->toBe(1)
        ->and($installments[1]->order)->toBe(2);
});

test('counterparty cannot pay via policy when status is in progress', function () {
    $guarantorRequest = GuarantorRequest::factory()->inProgress()->create();
    $counterparty = $guarantorRequest->counterparty;

    expect(Gate::forUser($counterparty)->allows('pay', $guarantorRequest))->toBeFalse();
});

test('stranger cannot view guarantor request', function () {
    $guarantorRequest = GuarantorRequest::factory()->create();
    $stranger = User::factory()->create();

    expect(Gate::forUser($stranger)->allows('view', $guarantorRequest))->toBeFalse();
});
