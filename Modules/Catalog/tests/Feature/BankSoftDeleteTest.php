<?php

use Illuminate\Http\UploadedFile;
use Modules\Catalog\Http\Controllers\Dashboard\BankController;
use Modules\Catalog\Http\Resources\Api\V1\BankResource;
use Modules\Catalog\Models\Bank;
use Modules\Classifieds\Http\Resources\Api\CarAdvisementResource;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorRequest;

/**
 * SoftDeletes on Bank: historical Guarantor/CarAdvisement FKs stay intact
 * and remain resolvable via withTrashed relationships (logo/media preserved).
 */
test('deleting a Bank soft-deletes it (deleted_at set), row still exists in the database', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete banks']);
    $bank = Bank::factory()->create(['translations' => geoNameTranslations('SoftDelete Me')]);

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $bank))
        ->assertRedirect(route('dashboard.banks.index'))
        ->assertSessionHas('success', __('data deleted successfully'));

    expect(Bank::query()->whereKey($bank->id)->exists())->toBeFalse()
        ->and(Bank::withTrashed()->whereKey($bank->id)->exists())->toBeTrue()
        ->and(Bank::withTrashed()->find($bank->id)?->trashed())->toBeTrue()
        ->and(Bank::withTrashed()->find($bank->id)?->deleted_at)->not->toBeNull();
});

test('a soft-deleted Bank no longer appears in the admin Banks index by default', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show banks', 'delete banks']);
    $bank = Bank::factory()->create(['translations' => geoNameTranslations('HiddenFromIndex')]);

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $bank))
        ->assertRedirect(route('dashboard.banks.index'));

    $this->actingAs($admin, 'admin')
        ->get(action([BankController::class, 'index'], ['search' => 'HiddenFromIndex']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Banks/Index')
            ->has('rows.data', 0)
        );
});

test('a soft-deleted Bank no longer appears in the public catalog API', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete banks']);
    $bank = Bank::factory()->create(['translations' => geoNameTranslations('SoftDeletedCatalog')]);

    $this->getJson('/api/v1/catalog/banks?search=SoftDeletedCatalog')
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.id', $bank->id);

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $bank))
        ->assertRedirect(route('dashboard.banks.index'));

    $items = $this->getJson('/api/v1/catalog/banks?search=SoftDeletedCatalog')
        ->assertSuccessful()
        ->json('data.items');

    expect(collect($items)->pluck('id')->all())->not->toContain($bank->id);
});

test('a Guarantor record referencing a since-soft-deleted bank still resolves the bank\'s name/logo correctly when explicitly loaded (withTrashed), proving historical data is preserved', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete banks']);

    $bank = Bank::factory()->create(['translations' => geoNameTranslations('Historical Guarantor Bank')]);
    $bank->addMedia(UploadedFile::fake()->image('hist-g.png', 32, 32))->toMediaCollection('logo');
    $logoUrl = $bank->fresh()->getLogoUrl();

    $guarantorRequest = GuarantorRequest::factory()->company()->create();
    $detail = GuarantorCompanyDetail::query()->create([
        'guarantor_request_id' => $guarantorRequest->id,
        'company_name' => 'Acme',
        'commercial_register' => '123',
        'authorized_name' => 'Auth',
        'authorized_id_number' => '1',
        'authorization_type' => AuthorizationTypeEnum::Owner,
        'requester_account_holder' => 'Holder',
        'requester_iban' => 'SA0380000000608010167519',
        'requester_bank_id' => $bank->id,
        'counterparty_account_holder' => 'CP Holder',
    ]);

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $bank))
        ->assertRedirect(route('dashboard.banks.index'));

    expect($detail->fresh()->requester_bank_id)->toBe($bank->id);

    $detail->load(['requesterBank.translations', 'requesterBank.media']);
    $resolved = $detail->requesterBank;

    expect($resolved)->not->toBeNull()
        ->and($resolved->trashed())->toBeTrue()
        ->and($resolved->name)->toBe('Historical Guarantor Bank EN')
        ->and($resolved->getLogoUrl())->toBe($logoUrl);

    $explicit = Bank::withTrashed()->with(['translations', 'media'])->findOrFail($bank->id);
    $payload = BankResource::make($explicit)->resolve();

    expect($payload['id'])->toBe($bank->id)
        ->and($payload['name'])->toBe('Historical Guarantor Bank EN')
        ->and($payload['logo'])->toBe($logoUrl);
});

test('a CarAdvisement record referencing a since-soft-deleted bank behaves the same way', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete banks']);

    $bank = Bank::factory()->create(['translations' => geoNameTranslations('Historical Car Bank')]);
    $bank->addMedia(UploadedFile::fake()->image('hist-c.png', 32, 32))->toMediaCollection('logo');
    $logoUrl = $bank->fresh()->getLogoUrl();

    $advisement = CarAdvisement::factory()->create(['bank_id' => $bank->id]);

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $bank))
        ->assertRedirect(route('dashboard.banks.index'));

    expect($advisement->fresh()->bank_id)->toBe($bank->id);

    $advisement->load(['bank.translations', 'bank.media']);
    $resolved = $advisement->bank;

    expect($resolved)->not->toBeNull()
        ->and($resolved->trashed())->toBeTrue()
        ->and($resolved->name)->toBe('Historical Car Bank EN')
        ->and($resolved->getLogoUrl())->toBe($logoUrl);

    $payload = CarAdvisementResource::make($advisement)
        ->response(request())
        ->getData(true);

    expect($payload['bank']['id'])->toBe($bank->id)
        ->and($payload['bank']['name'])->toBe('Historical Car Bank EN')
        ->and($payload['bank']['logo'])->toBe($logoUrl);
});

test('is_active toggling is completely unaffected by this change — regression', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit banks', 'show banks']);

    $bank = Bank::factory()->create([
        'is_active' => true,
        'translations' => geoNameTranslations('ActiveToggleBank'),
    ]);

    $this->actingAs($admin, 'admin')
        ->put(action([BankController::class, 'update'], $bank), [
            'translations' => geoNameTranslations('ActiveToggleBank'),
            'is_active' => false,
        ])
        ->assertRedirect(route('dashboard.banks.index'))
        ->assertSessionHasNoErrors();

    $bank->refresh();
    expect($bank->trashed())->toBeFalse()
        ->and($bank->is_active)->toBeFalse();

    $this->actingAs($admin, 'admin')
        ->get(action([BankController::class, 'edit'], $bank))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Banks/Edit')
            ->where('row.id', $bank->id)
            ->where('row.is_active', false)
        );

    $apiNames = collect($this->getJson('/api/v1/catalog/banks?search=ActiveToggleBank')
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('name')
        ->all();

    expect($apiNames)->not->toContain('ActiveToggleBank EN');
});

test('Banks admin table now has a Delete action in the row dropdown, styled destructive, with a confirmation modal', function (): void {
    $path = resource_path('js/apps/admin/pages/Banks/Index.tsx');
    expect(file_exists($path))->toBeTrue();

    $source = (string) file_get_contents($path);

    expect($source)->toContain("from '@/shared/components/Table/partials/confirm-action'")
        ->and($source)->toContain('ConfirmAction')
        ->and($source)->toContain("hasPermission('delete banks')")
        ->and($source)->toContain('BankController.destroy')
        ->and($source)->toContain("title={t('delete')}");
});

test('deleting a bank shows a success toast after confirmation (using the flash-inclusive partial reload pattern fixed earlier this session, not the buggy only:[\'rows\'] pattern)', function (): void {
    $path = resource_path('js/apps/admin/pages/Banks/Index.tsx');
    $source = (string) file_get_contents($path);

    expect(preg_match(
        "/router\.delete\([\s\S]*?only:\s*\[\s*'rows'\s*,\s*'flash'\s*\]/",
        $source
    ))->toBe(1)
        ->and(preg_match(
            "/router\.delete\([\s\S]*?only:\s*\[\s*'rows'\s*\]/",
            $source
        ))->toBe(0);
});
