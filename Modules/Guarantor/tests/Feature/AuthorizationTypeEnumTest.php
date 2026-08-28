<?php

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Models\GuarantorCompanyDetail;

beforeEach(function () {
    Notification::fake();
});

test('AuthorizationTypeEnum no longer has a PowerOfAttorney case', function () {
    $values = array_map(fn (AuthorizationTypeEnum $case) => $case->value, AuthorizationTypeEnum::cases());

    expect($values)->not->toContain('power_of_attorney')
        ->and(collect(AuthorizationTypeEnum::cases())->pluck('name')->all())->not->toContain('PowerOfAttorney');
});

test('AuthorizationTypeEnum now has Owner and Manager cases, alongside unchanged Agency', function () {
    expect(AuthorizationTypeEnum::Owner->value)->toBe('owner')
        ->and(AuthorizationTypeEnum::Manager->value)->toBe('manager')
        ->and(AuthorizationTypeEnum::Agency->value)->toBe('agency')
        ->and(AuthorizationTypeEnum::cases())->toHaveCount(3);
});

test('Company guarantor creation accepts owner, manager, or agency as authorization_type — all three validate and persist correctly', function (string $authorizationType) {
    $requester = User::factory()->create();
    $counterpartyPhone = '0509988776';
    User::factory()->create(['phone' => (string) Phone::make($counterpartyPhone)]);
    Sanctum::actingAs($requester);

    $files = companyGuarantorFiles();
    if ($authorizationType === 'agency') {
        $files['power_of_attorney_document'] = UploadedFile::fake()->create('poa.pdf', 100, 'application/pdf');
    }

    $payload = array_merge(companyGuarantorPayload([
        'counterparty_phone' => $counterpartyPhone,
        'authorization_type' => $authorizationType,
    ]), $files);

    $response = test()->post(
        route('api.v1.guarantor.guarantor.store.company'),
        $payload,
        ['Accept' => 'application/json'],
    );

    $response->assertSuccessful();

    $detail = GuarantorCompanyDetail::query()
        ->where('authorization_type', $authorizationType)
        ->latest('id')
        ->first();

    expect($detail)->not->toBeNull()
        ->and($detail->authorization_type)->toBe(AuthorizationTypeEnum::from($authorizationType));
})->with(['owner', 'manager', 'agency']);

test('Company guarantor creation rejects power_of_attorney as an invalid enum value now', function () {
    ['counterparty' => $counterparty] = setupGuarantorActors();

    $validator = validateCompanyGuarantorRequest(
        companyGuarantorPayload([
            'counterparty_phone' => (string) $counterparty->phone,
            'authorization_type' => 'power_of_attorney',
        ]),
        companyGuarantorFiles(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('authorization_type'))->toBeTrue();
});

test('all Guarantor and Catalog test files are free of power_of_attorney authorization type references', function () {
    $paths = [
        base_path('Modules/Guarantor/tests'),
        base_path('Modules/Catalog/tests/Feature/BankCatalogTest.php'),
    ];

    $skip = realpath(base_path('Modules/Guarantor/tests/Feature/AuthorizationTypeEnumTest.php'));
    $matches = [];

    foreach ($paths as $path) {
        if (is_file($path)) {
            $files = [new SplFileInfo($path)];
        } else {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            $files = iterator_to_array($iterator);
        }

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            if ($skip !== false && realpath($file->getPathname()) === $skip) {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            if ($content === false) {
                continue;
            }

            if (preg_match('/(?<![\w])power_of_attorney(?!_document)|PowerOfAttorney/', $content) === 1) {
                $matches[] = $file->getPathname();
            }
        }
    }

    expect($matches)->toBeEmpty('Found power_of_attorney references in: '.implode(', ', $matches));
});

test('labels for owner/manager/agency are correctly translated in all 4 locales (en/ar/hi/ur)', function () {
    $expected = [
        'en' => ['owner' => 'Owner', 'manager' => 'Manager', 'agency' => 'Agency'],
        'ar' => ['owner' => 'مالك', 'manager' => 'مدير', 'agency' => 'وكالة'],
        'hi' => ['owner' => 'मालिक', 'manager' => 'प्रबंधक', 'agency' => 'एजेंसी'],
        'ur' => ['owner' => 'مالک', 'manager' => 'منیجر', 'agency' => 'ایجنسی'],
    ];

    foreach ($expected as $locale => $labels) {
        app()->setLocale($locale);

        expect(AuthorizationTypeEnum::Owner->toString())->toBe($labels['owner'])
            ->and(AuthorizationTypeEnum::Manager->toString())->toBe($labels['manager'])
            ->and(AuthorizationTypeEnum::Agency->toString())->toBe($labels['agency']);
    }
});
