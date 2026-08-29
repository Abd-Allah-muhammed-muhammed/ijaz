<?php

use App\Models\User;
use App\Support\LookupCache;
use App\Support\Phone;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Actions\Guarantor\CalculateGuarantorFeesAction;
use Modules\Guarantor\Actions\Guarantor\CreateCompanyGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CreateIndividualGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\DetermineGuarantorHeldAmountAction;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\DTOs\CompanyDetailData;
use Modules\Guarantor\DTOs\GuarantorData;
use Modules\Guarantor\DTOs\GuarantorUploadData;
use Modules\Guarantor\DTOs\InstallmentData;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Settings\Http\Controllers\Api\V1\SettingController;
use Modules\Settings\Http\Controllers\Dashboard\SettingController as DashboardSettingController;
use Modules\Settings\Models\Setting;

const PLATFORM_FEE_COUNTERPARTY_PHONE = '0509988776';

beforeEach(function (): void {
    Notification::fake();
    cache()->forget('settings');
    app()->forgetInstance('settings');
    LookupCache::forget('settings:public');
});

function platformFeeCounterpartyPhone(): string
{
    return (string) Phone::make(PLATFORM_FEE_COUNTERPARTY_PHONE);
}

/**
 * @return array{requester: User, counterparty: User}
 */
function platformFeeActors(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create(['phone' => platformFeeCounterpartyPhone()]);
    Sanctum::actingAs($requester);

    return compact('requester', 'counterparty');
}

function platformFeeIndividualUploads(): GuarantorUploadData
{
    return GuarantorUploadData::fromIndividualRequest(Request::create('/', 'POST', [], [], [
        'signature' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
    ]));
}

function platformFeeCompanyUploads(): GuarantorUploadData
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

test('creating an Individual guarantor computes fees as round(amount * guarantee_fee_percent / 100, 2), not the old flat 10', function () {
    setGuarantorSetting('guarantee_fee_percent', '2.5');
    ['requester' => $requester] = platformFeeActors();

    // 1000 * 2.5 / 100 = 25.00 — not the old flat 10
    $guarantorRequest = app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Percent fee individual',
            description: 'Fee snapshot test',
            amount: 1000,
            counterparty_phone: PLATFORM_FEE_COUNTERPARTY_PHONE,
        ),
        $requester,
        platformFeeIndividualUploads(),
    );

    expect((float) $guarantorRequest->fees)->toBe(25.0)
        ->and((float) $guarantorRequest->total)->toBe(1025.0)
        ->and((float) $guarantorRequest->fees)->not->toBe(10.0);
});

test('creating a Company guarantor computes fees the same way, based on total_amount', function () {
    setGuarantorSetting('guarantee_fee_percent', '2.5');
    ['requester' => $requester] = platformFeeActors();

    // GuarantorData.amount is mapped from company total_amount
    $guarantorRequest = app(CreateCompanyGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Construction',
            description: '',
            amount: 2000,
            counterparty_phone: PLATFORM_FEE_COUNTERPARTY_PHONE,
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
            new InstallmentData(1, 1000, now()->addDays(3)->toDateString()),
            new InstallmentData(2, 1000, now()->addDays(30)->toDateString()),
        ],
        $requester,
        platformFeeCompanyUploads(),
    );

    // 2000 * 2.5 / 100 = 50.00
    expect((float) $guarantorRequest->fees)->toBe(50.0)
        ->and((float) $guarantorRequest->amount)->toBe(2000.0)
        ->and((float) $guarantorRequest->total)->toBe(2050.0);
});

test('changing guarantee_fee_percent in settings changes the fee on the NEXT creation, without any code deploy', function () {
    setGuarantorSetting('guarantee_fee_percent', '2.5');
    ['requester' => $requester] = platformFeeActors();

    $first = app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'First create',
            description: 'At 2.5%',
            amount: 1000,
            counterparty_phone: PLATFORM_FEE_COUNTERPARTY_PHONE,
        ),
        $requester,
        platformFeeIndividualUploads(),
    );

    expect((float) $first->fees)->toBe(25.0);

    setGuarantorSetting('guarantee_fee_percent', '5');

    $second = app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Second create',
            description: 'At 5%',
            amount: 1000,
            counterparty_phone: PLATFORM_FEE_COUNTERPARTY_PHONE,
        ),
        $requester,
        platformFeeIndividualUploads(),
    );

    expect((float) $second->fees)->toBe(50.0)
        ->and((float) $first->fresh()->fees)->toBe(25.0);
});

test('an existing GuarantorRequest\'s fees value is completely unaffected by later settings changes — snapshot confirmed, no retroactive recalculation', function () {
    setGuarantorSetting('guarantee_fee_percent', '2.5');
    ['requester' => $requester] = platformFeeActors();

    $guarantorRequest = app(CreateIndividualGuarantorAction::class)->handle(
        new GuarantorData(
            title: 'Snapshot row',
            description: 'Must not change',
            amount: 1000,
            counterparty_phone: PLATFORM_FEE_COUNTERPARTY_PHONE,
        ),
        $requester,
        platformFeeIndividualUploads(),
    );

    expect((float) $guarantorRequest->fees)->toBe(25.0);

    setGuarantorSetting('guarantee_fee_percent', '10');

    expect((float) $guarantorRequest->fresh()->fees)->toBe(25.0)
        ->and(app(CalculateGuarantorFeesAction::class)->handle(1000))->toBe(100.0);
});

test('installment fee proration (ReleaseInstallmentAction, DetermineGuarantorHeldAmountAction) is completely unchanged — regression, still uses the stored fees value exactly as before', function () {
    // Stored snapshot fees=10 with amount=1000 / installment=500 — classic proration case.
    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create([
        'amount' => 1000,
        'fees' => 10,
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $held = app(DetermineGuarantorHeldAmountAction::class)->handle($guarantorRequest->fresh(['installments']));

    // round(500/1000*10, 2) = 5.00 — identical to pre-change proration
    expect($held->fee)->toBe(5.0)
        ->and($held->gross)->toBe(500.0)
        ->and($held->net)->toBe(495.0);

    $requester = $guarantorRequest->requester;
    $requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    app(ReleaseInstallmentAction::class)->handle($installment);

    $installment->refresh();
    $requester->wallet->refresh();

    expect($installment->status)->toBe(InstallmentStatusEnum::Released)
        ->and((float) $requester->wallet->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->balance)->toBe(495.0);
});

test('the guarantee_fee_percent setting appears on the existing generic Settings dashboard page under the guarantor group, with no new dashboard code required', function () {
    withoutSettingsDashboardLocaleMiddleware();
    $admin = createSettingsDashboardAdmin(['show settings']);

    setGuarantorSetting('guarantee_fee_percent', '2.5');

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardSettingController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Settings/Index')
            ->has('groups.guarantor')
            ->where('groups.guarantor', function ($rows) {
                $keys = collect($rows)->pluck('key');

                return $keys->contains('guarantee_fee_percent')
                    && ! $keys->contains('guarantee_fee');
            })
        );
});

test('the old guarantee_fee key no longer exists in settings after this change — confirmed removed from seeder and DB', function () {
    $this->seed(SettingsSeeder::class);

    expect(Setting::query()->where('key', 'guarantee_fee')->exists())->toBeFalse()
        ->and(Setting::query()->where('key', 'guarantee_fee_percent')->value('content'))->toBe('2.5')
        ->and(app('settings')->get('guarantee_fee'))->toBeNull()
        ->and(app('settings')->get('guarantee_fee_percent'))->toBe('2.5');

    $response = $this->getJson(action([SettingController::class, 'settings']));

    $response->assertSuccessful()
        ->assertJsonPath('data.guarantee_fee_percent', '2.5')
        ->assertJsonMissing(['guarantee_fee' => '20']);

    expect($response->json('data'))->not->toHaveKey('guarantee_fee');
});
