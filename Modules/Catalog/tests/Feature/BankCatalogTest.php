<?php

use App\Models\Admin;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Contracts\Repositories\BankRepositoryInterface;
use Modules\Catalog\Database\Seeders\BanksSeeder;
use Modules\Catalog\Http\Controllers\Dashboard\BankController;
use Modules\Catalog\Models\Bank;
use Modules\Geo\Http\Controllers\Dashboard\CityController;
use Modules\Geo\Http\Controllers\Dashboard\RegionController;
use Modules\Geo\Models\Region;
use Modules\Guarantor\Models\GuarantorRequest;
use Spatie\Permission\Models\Role;

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

test('GET /api/v1/catalog/banks no longer includes a translations field', function () {
    Bank::factory()->create(['translations' => geoNameTranslations('NoTranslations')]);

    $response = $this->getJson('/api/v1/catalog/banks?search=NoTranslations');
    $response->assertSuccessful();

    expect($response->json('data.items.0'))->not->toHaveKey('translations');
});

test('GET /api/v1/catalog/banks returns logo, not logo_url', function () {
    Bank::factory()->create(['translations' => geoNameTranslations('MobileShape')]);

    $response = $this->getJson('/api/v1/catalog/banks?search=MobileShape');
    $response->assertSuccessful();

    expect(array_keys($response->json('data.items.0')))->toBe(['id', 'name', 'logo', 'is_active'])
        ->and($response->json('data.items.0'))->not->toHaveKey('logo_url')
        ->and($response->json('data.items.0.name'))->toBe('MobileShape EN');
});

test('Dashboard bank resource returns logo, not logo_url', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show banks']);
    $bank = Bank::factory()->create(['translations' => geoNameTranslations('DashboardLogo')]);
    $bank->addMedia(UploadedFile::fake()->image('bank-logo.png', 32, 32))->toMediaCollection('logo');

    $this->actingAs($admin, 'admin')
        ->get(action([BankController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Banks/Index')
            ->where(
                'rows.data',
                fn ($rows) => collect($rows)->contains(
                    fn ($row) => (int) $row['id'] === $bank->id
                        && array_key_exists('logo', $row)
                        && ! array_key_exists('logo_url', $row)
                )
            )
        );
});

test('the Dashboard banks admin resource (used by the edit form) still exposes all 4 locale translations — unaffected, this removal is public-API-only', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit banks']);
    $bank = Bank::factory()->create(['translations' => geoNameTranslations('EditForm')]);

    $this->actingAs($admin, 'admin')
        ->get(action([BankController::class, 'edit'], $bank))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Banks/Edit')
            ->has('row.translations.en')
            ->has('row.translations.ar')
            ->has('row.translations.hi')
            ->has('row.translations.ur')
        );
});

test('paginateForApi does not eager-load translations since the public API resource no longer uses them', function () {
    Bank::factory()->create(['translations' => geoNameTranslations('EagerProbe')]);

    $bank = app(BankRepositoryInterface::class)
        ->paginateForApi('EagerProbe', 10)
        ->first();

    expect($bank)->not->toBeNull()
        ->and($bank->relationLoaded('translations'))->toBeFalse();
});

test('Dashboard banks index returns the standard flat paginated shape (matching Region/ElectronicBrand), not the API-style {items, total} wrapper', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show banks']);
    Bank::factory()->count(2)->create();

    $this->actingAs($admin, 'admin')
        ->get(action([BankController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Banks/Index')
            ->has('rows.data', 2)
            ->missing('rows.data.items')
        );
});

test('a non-root admin with the content-manager role can access /dashboard/banks after permissions are seeded', function () {
    $this->seed(RolePermissionSeeder::class);
    withoutGeoDashboardLocaleMiddleware();

    $admin = Admin::query()->create([
        'name' => 'Content Manager Banks',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
        'root' => false,
    ]);
    $admin->assignRole(Role::findByName('content-manager', 'admin'));

    $this->actingAs($admin->fresh(), 'admin')
        ->get(action([BankController::class, 'index']))
        ->assertSuccessful();
});

test('a non-root admin without any bank permission is correctly denied (403) — confirms permissions are actually enforced, not just present', function () {
    $this->seed(RolePermissionSeeder::class);
    withoutGeoDashboardLocaleMiddleware();

    $admin = Admin::query()->create([
        'name' => 'No Bank Permission Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
        'root' => false,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([BankController::class, 'index']))
        ->assertForbidden();
});

test('existing regions/cities dashboard index tests still pass — regression, confirming Part A\'s scope decision does not break anything', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show regions', 'show cities']);
    Region::factory()->count(2)->create();

    $this->actingAs($admin, 'admin')
        ->get(action([RegionController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Regions/Index')
            ->has('rows.data', 2)
        );

    $this->actingAs($admin, 'admin')
        ->get(action([CityController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Dashboard/Cities/Index'));
});

test('GET /api/v1/catalog/banks returns items with id, name (current locale), and logo, matching the mobile catalog contract shape', function () {
    Bank::factory()->create(['translations' => geoNameTranslations('Riyad')]);

    $response = $this->getJson('/api/v1/catalog/banks');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys([
            'items', 'total', 'count', 'per_page', 'current_page', 'last_page', 'has_more_pages',
        ])
        ->and($json['data']['items'])->toBeArray()->not->toBeEmpty();

    expect(array_keys($json['data']['items'][0]))->toBe(['id', 'name', 'logo', 'is_active']);
});

test('the logo value itself is unchanged (same URL/null semantics) — only the key name changes', function () {
    $bank = Bank::factory()->create(['translations' => geoNameTranslations('LogoValue')]);
    $bank->addMedia(UploadedFile::fake()->image('logo-value.png', 32, 32))->toMediaCollection('logo');

    $apiItem = $this->getJson('/api/v1/catalog/banks?search=LogoValue')
        ->assertSuccessful()
        ->json('data.items.0');

    expect($apiItem['logo'])->toBe($bank->fresh()->getLogoUrl())
        ->and($apiItem)->not->toHaveKey('logo_url');

    $bankWithoutLogo = Bank::factory()->create(['translations' => geoNameTranslations('NoLogoBank')]);

    $nullLogoItem = $this->getJson('/api/v1/catalog/banks?search=NoLogoBank')
        ->assertSuccessful()
        ->json('data.items.0');

    expect($nullLogoItem['logo'])->toBeNull()
        ->and($nullLogoItem)->not->toHaveKey('logo_url');
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
        'authorization_type' => 'owner',
        'requester_account_holder' => 'Holder',
        'requester_iban' => 'SA1234567890123456789012',
        'requester_bank_id' => $bank->id,
        'counterparty_account_holder' => 'CP Holder',
        'terms_notes' => 'Payment subject to milestone approval.',
    ]);

    expect($detail->fresh()->terms_notes)->toBe('Payment subject to milestone approval.');
});
