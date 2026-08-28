<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Database\Seeders\BanksSeeder;
use Modules\Catalog\Http\Controllers\Dashboard\BankController;
use Modules\Catalog\Models\Bank;
use Modules\Guarantor\Models\GuarantorRequest;

test('a Bank can be created with per-locale translations (name) and a logo, via the Dashboard', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['create banks']);

    $translations = geoNameTranslations('SNB');

    $this->actingAs($admin, 'admin')
        ->post(action([BankController::class, 'store']), [
            'translations' => $translations,
            'is_active' => true,
            'logo' => UploadedFile::fake()->image('snb.png', 64, 64),
        ])
        ->assertRedirect(route('dashboard.banks.index'));

    $bank = Bank::query()->whereTranslation('name', 'SNB EN', 'en')->first();

    expect($bank)->not->toBeNull()
        ->and($bank->translate('ar')->name)->toBe('SNB AR')
        ->and($bank->is_active)->toBeTrue()
        ->and($bank->getMedia('logo'))->toHaveCount(1);
});

test('an inactive Bank is excluded from the mobile catalog API but still visible/editable in the Dashboard', function () {
    $active = Bank::factory()->create(['translations' => geoNameTranslations('DashboardActive')]);
    $inactive = Bank::factory()->inactive()->create(['translations' => geoNameTranslations('DashboardHidden')]);

    $apiResponse = $this->getJson('/api/v1/catalog/banks?search=Dashboard');
    $apiResponse->assertSuccessful();

    $apiNames = collect($apiResponse->json('data.items'))->pluck('name')->all();
    expect($apiNames)->toContain('DashboardActive EN')
        ->and($apiNames)->not->toContain('DashboardHidden EN');

    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show banks', 'edit banks']);

    $this->actingAs($admin, 'admin')
        ->get(action([BankController::class, 'edit'], $inactive))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Banks/Edit')
            ->where('row.id', $inactive->id)
            ->where('row.is_active', false)
        );
});

test('GET /api/v1/catalog/banks returns items with id, name (current locale), translations, and logo_url, matching the existing catalog contract shape used by regions/cities', function () {
    Bank::factory()->create(['translations' => geoNameTranslations('Riyad')]);

    $response = $this->getJson('/api/v1/catalog/banks');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys([
            'items', 'total', 'count', 'per_page', 'current_page', 'last_page', 'has_more_pages',
        ])
        ->and($json['data']['items'])->toBeArray()->not->toBeEmpty();

    expect(array_keys($json['data']['items'][0]))->toBe(['id', 'name', 'translations', 'logo_url', 'is_active']);
});

test('Bank admin CRUD respects the same permission pattern as Region (show/create/edit/delete banks)', function () {
    withoutGeoDashboardLocaleMiddleware();
    $bank = Bank::factory()->create();

    $this->actingAs(createGeoDashboardAdmin([]), 'admin')
        ->get(action([BankController::class, 'index']))
        ->assertForbidden();

    $this->actingAs(createGeoDashboardAdmin(['show banks']), 'admin')
        ->get(action([BankController::class, 'index']))
        ->assertSuccessful();

    $this->actingAs(createGeoDashboardAdmin(['create banks']), 'admin')
        ->get(action([BankController::class, 'create']))
        ->assertSuccessful();

    $this->actingAs(createGeoDashboardAdmin(['edit banks']), 'admin')
        ->get(action([BankController::class, 'edit'], $bank))
        ->assertSuccessful();

    $this->actingAs(createGeoDashboardAdmin(['delete banks']), 'admin')
        ->delete(action([BankController::class, 'destroy'], $bank))
        ->assertRedirect(route('dashboard.banks.index'));

    expect(Bank::query()->whereKey($bank->id)->exists())->toBeFalse();
});

test('the BanksSeeder is idempotent — running it twice does not create duplicate banks', function () {
    $this->seed(BanksSeeder::class);
    $afterFirst = Bank::query()->count();

    $this->seed(BanksSeeder::class);

    expect(Bank::query()->count())->toBe($afterFirst)
        ->and($afterFirst)->toBe(14);
});

test('the combined migration adds exactly the 3 new columns (requester_bank_id, counterparty_bank_id, terms_notes) on guarantor_company_details in one file — no separate migrations', function () {
    $migrationDirectory = base_path('Modules/Guarantor/database/migrations');
    $matchingFiles = collect(File::files($migrationDirectory))
        ->filter(function ($file) {
            $contents = File::get($file->getPathname());

            return str_contains($contents, 'requester_bank_id')
                && str_contains($contents, 'counterparty_bank_id')
                && str_contains($contents, 'terms_notes');
        })
        ->values();

    expect($matchingFiles)->toHaveCount(1)
        ->and(Schema::hasColumn('guarantor_company_details', 'requester_bank_id'))->toBeTrue()
        ->and(Schema::hasColumn('guarantor_company_details', 'counterparty_bank_id'))->toBeTrue()
        ->and(Schema::hasColumn('guarantor_company_details', 'terms_notes'))->toBeTrue();
});

test('terms_notes persists correctly on GuarantorCompanyDetail', function () {
    $bank = Bank::factory()->create();
    $guarantorRequest = GuarantorRequest::factory()->company()->pendingAdmin()->create();

    $detail = $guarantorRequest->companyDetail()->create([
        'company_name' => 'Acme Corp',
        'commercial_register' => 'CR-1',
        'authorized_name' => 'Auth Name',
        'authorized_id_number' => '123',
        'authorization_type' => 'power_of_attorney',
        'requester_account_holder' => 'Holder',
        'requester_iban' => 'SA1234567890123456789012',
        'requester_bank_id' => $bank->id,
        'counterparty_account_holder' => 'CP Holder',
        'terms_notes' => 'Payment subject to milestone approval.',
    ]);

    expect($detail->fresh()->terms_notes)->toBe('Payment subject to milestone approval.');
});
