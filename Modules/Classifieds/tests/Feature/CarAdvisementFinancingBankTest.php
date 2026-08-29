<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Modules\Catalog\Http\Resources\Api\V1\BankResource;
use Modules\Catalog\Models\Bank;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Classifieds\Enums\OperationEnum;
use Modules\Classifieds\Enums\UsageStatusEnum;
use Modules\Classifieds\Http\Controllers\Api\V1\CarAdvisementController;
use Modules\Classifieds\Http\Controllers\Dashboard\CarAdvisementController as DashboardCarAdvisementController;
use Modules\Classifieds\Http\Resources\Api\CarAdvisementResource;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->carBrand = CarBrand::factory()->create();
    $this->carType = CarType::factory()->create();
    $this->carCategory = CarCategory::factory()->create();
    $this->region = Region::factory()->create();
    $this->city = City::factory()->create(['region_id' => $this->region->id]);
});

/**
 * @return array<string, mixed>
 */
function carAdBankPayload(object $ctx, array $overrides = []): array
{
    return array_merge([
        'title' => 'Financed Car',
        'description' => 'Car with optional financing bank',
        'operation' => OperationEnum::SALE->value,
        'usage_status' => UsageStatusEnum::NEW->value,
        'car_brand_id' => $ctx->carBrand->id,
        'car_type_id' => $ctx->carType->id,
        'car_category_id' => $ctx->carCategory->id,
        'city_id' => $ctx->city->id,
        'region_id' => $ctx->region->id,
        'year' => 2022,
        'price' => 80000,
        'show_price' => true,
        'phone' => '966501234567',
    ], $overrides);
}

/**
 * @return list<string>
 */
function carAdPublicBankShapeKeys(): array
{
    return ['id', 'name', 'logo', 'is_active'];
}

test('car ad creation accepts an optional bank_id, validated against active banks', function () {
    Sanctum::actingAs($this->user);

    $bank = Bank::factory()->create([
        'translations' => geoNameTranslations('Active Financing Bank'),
    ]);
    $bank->addMedia(UploadedFile::fake()->image('logo.png'))
        ->toMediaCollection('logo');

    $this->postJson(action([CarAdvisementController::class, 'store']), carAdBankPayload($this, [
        'bank_id' => $bank->id,
    ]))
        ->assertOk()
        ->assertJsonPath('data.bank.id', $bank->id)
        ->assertJsonPath('data.bank.name', 'Active Financing Bank EN')
        ->assertJsonPath('data.bank.is_active', true);

    $this->assertDatabaseHas('car_advisements', [
        'user_id' => $this->user->id,
        'title' => 'Financed Car',
        'bank_id' => $bank->id,
    ]);
});

test('car ad creation without a bank_id still succeeds — financing is optional', function () {
    Sanctum::actingAs($this->user);

    $this->postJson(action([CarAdvisementController::class, 'store']), carAdBankPayload($this))
        ->assertOk()
        ->assertJsonPath('data.bank', null);

    $this->assertDatabaseHas('car_advisements', [
        'user_id' => $this->user->id,
        'title' => 'Financed Car',
        'bank_id' => null,
    ]);
});

test('an invalid/inactive bank_id is rejected with a clear validation error', function () {
    Sanctum::actingAs($this->user);

    $inactive = Bank::factory()->inactive()->create([
        'translations' => geoNameTranslations('Inactive Financing Bank'),
    ]);

    $this->postJson(action([CarAdvisementController::class, 'store']), carAdBankPayload($this, [
        'bank_id' => $inactive->id,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bank_id']);

    $this->postJson(action([CarAdvisementController::class, 'store']), carAdBankPayload($this, [
        'bank_id' => 999_999,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['bank_id']);
});

test('the admin CarAdvisement show page displays the selected bank (name + logo) when present', function () {
    withoutClassifiedsDashboardLocaleMiddleware();

    $admin = createClassifiedsDashboardAdmin(['show carAdvisements']);
    $bank = Bank::factory()->create([
        'translations' => geoNameTranslations('Admin Show Bank'),
    ]);
    $bank->addMedia(UploadedFile::fake()->image('admin-bank.png'))
        ->toMediaCollection('logo');

    $advisement = CarAdvisement::factory()->create([
        'bank_id' => $bank->id,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardCarAdvisementController::class, 'show'], $advisement))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/CarAdvisement/Show')
            ->where('row.bank.id', $bank->id)
            ->where('row.bank.name', 'Admin Show Bank EN')
            ->where('row.bank.logo', $bank->fresh()->getLogoUrl())
            ->where('row.bank.is_active', true));
});

test('the admin show page shows a clear "no financing bank selected" state when bank_id is null', function () {
    withoutClassifiedsDashboardLocaleMiddleware();

    $admin = createClassifiedsDashboardAdmin(['show carAdvisements']);
    $advisement = CarAdvisement::factory()->create([
        'bank_id' => null,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardCarAdvisementController::class, 'show'], $advisement))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/CarAdvisement/Show')
            ->where('row.bank', null));
});

test('CarAdvisementResource exposes bank using the same {id, name, logo, is_active} shape as the existing Catalog BankResource — reuse, not a new shape', function () {
    $bank = Bank::factory()->create([
        'translations' => geoNameTranslations('Shape Match Bank'),
    ]);
    $bank->addMedia(UploadedFile::fake()->image('shape.png'))
        ->toMediaCollection('logo');

    $advisement = CarAdvisement::factory()->create([
        'bank_id' => $bank->id,
    ]);
    $advisement->load(['bank.translations', 'bank.media']);

    $request = Request::create('/');
    $carPayload = CarAdvisementResource::make($advisement)->response($request)->getData(true);
    $expected = BankResource::make($bank)->response($request)->getData(true);

    expect(array_keys($carPayload['bank']))->toBe(carAdPublicBankShapeKeys())
        ->and($carPayload['bank'])->toBe($expected)
        ->and($carPayload['bank'])->not->toHaveKeys(['value', 'label', 'logo_url']);
});
