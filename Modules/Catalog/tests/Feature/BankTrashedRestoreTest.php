<?php

use Modules\Catalog\Http\Controllers\Dashboard\BankController;
use Modules\Catalog\Models\Bank;

test('the banks index endpoint accepts a trashed filter param returning only soft-deleted banks', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show banks', 'delete banks']);

    $active = Bank::factory()->create(['translations' => geoNameTranslations('ActiveListedBank')]);
    $trashed = Bank::factory()->create(['translations' => geoNameTranslations('TrashedListedBank')]);

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $trashed))
        ->assertRedirect(route('dashboard.banks.index'));

    $this->actingAs($admin, 'admin')
        ->get(action([BankController::class, 'index'], ['trashed' => 1]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Banks/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $trashed->id)
            ->where('prams.trashed', '1')
        );

    expect(Bank::query()->whereKey($active->id)->exists())->toBeTrue();
});

test('the default banks index (no filter) still excludes trashed banks — regression', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show banks', 'delete banks']);

    $active = Bank::factory()->create(['translations' => geoNameTranslations('DefaultIndexActive')]);
    $trashed = Bank::factory()->create(['translations' => geoNameTranslations('DefaultIndexTrashed')]);

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $trashed))
        ->assertRedirect(route('dashboard.banks.index'));

    $this->actingAs($admin, 'admin')
        ->get(action([BankController::class, 'index'], ['search' => 'DefaultIndex']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Banks/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $active->id)
        );
});

test('a new restore endpoint restores a soft-deleted bank (deleted_at set back to null)', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete banks']);
    $bank = Bank::factory()->create(['translations' => geoNameTranslations('RestoreMeBank')]);

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $bank))
        ->assertRedirect(route('dashboard.banks.index'));

    expect(Bank::withTrashed()->find($bank->id)?->trashed())->toBeTrue();

    $this->actingAs($admin, 'admin')
        ->from(route('dashboard.banks.index', ['trashed' => 1]))
        ->post(action([BankController::class, 'restore'], $bank))
        ->assertRedirect(route('dashboard.banks.index', ['trashed' => 1]))
        ->assertSessionHas('success', __('data restored successfully'));

    expect(Bank::query()->whereKey($bank->id)->exists())->toBeTrue()
        ->and(Bank::find($bank->id)?->trashed())->toBeFalse()
        ->and(Bank::find($bank->id)?->deleted_at)->toBeNull();
});

test('restoring a bank makes it reappear in the default index and the public catalog API again', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show banks', 'delete banks']);
    $bank = Bank::factory()->create(['translations' => geoNameTranslations('ReappearBank')]);

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $bank))
        ->assertRedirect(route('dashboard.banks.index'));

    $this->getJson('/api/v1/catalog/banks?search=ReappearBank')
        ->assertSuccessful();

    expect(collect($this->getJson('/api/v1/catalog/banks?search=ReappearBank')->json('data.items'))
        ->pluck('id')
        ->all())->not->toContain($bank->id);

    $this->actingAs($admin, 'admin')
        ->post(action([BankController::class, 'restore'], $bank))
        ->assertSessionHas('success');

    $this->actingAs($admin, 'admin')
        ->get(action([BankController::class, 'index'], ['search' => 'ReappearBank']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $bank->id)
        );

    expect(collect($this->getJson('/api/v1/catalog/banks?search=ReappearBank')
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id')
        ->all())->toContain($bank->id);
});

test('restore is permission-gated the same way delete/edit already are — not open to lower-privilege roles', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $bank = Bank::factory()->create(['translations' => geoNameTranslations('PermRestoreBank')]);
    $bank->delete();

    $this->actingAs(createGeoDashboardAdmin(['show banks']), 'admin')
        ->post(action([BankController::class, 'restore'], $bank))
        ->assertForbidden();

    $this->actingAs(createGeoDashboardAdmin(['edit banks']), 'admin')
        ->post(action([BankController::class, 'restore'], $bank))
        ->assertForbidden();

    $this->actingAs(createGeoDashboardAdmin(['delete banks']), 'admin')
        ->post(action([BankController::class, 'restore'], $bank))
        ->assertRedirect()
        ->assertSessionHas('success', __('data restored successfully'));

    expect(Bank::query()->whereKey($bank->id)->exists())->toBeTrue();
});

test('Banks Index has a way to switch to viewing trashed banks (tab/filter toggle)', function (): void {
    $path = resource_path('js/apps/admin/pages/Banks/Index.tsx');
    $source = (string) file_get_contents($path);

    expect($source)->toContain('changeTrashedFilter')
        ->and($source)->toContain("t('active')")
        ->and($source)->toContain("t('trashed')")
        ->and($source)->toContain('viewingTrashed')
        ->and($source)->toContain('Nav.Link');
});

test('a trashed bank row shows a Restore action instead of Edit/Delete', function (): void {
    $path = resource_path('js/apps/admin/pages/Banks/Index.tsx');
    $source = (string) file_get_contents($path);

    expect($source)->toContain('BankController.restore')
        ->and($source)->toContain("title={t('restore')}")
        ->and($source)->toContain('viewingTrashed')
        ->and($source)->toMatch('/viewingTrashed\s*\?\s*\[/')
        ->and($source)->toContain('BankController.destroy')
        ->and($source)->toContain('BankController.edit');
});

test('restoring shows a success toast (flash-inclusive reload, matching this session\'s established pattern)', function (): void {
    $path = resource_path('js/apps/admin/pages/Banks/Index.tsx');
    $source = (string) file_get_contents($path);

    expect(preg_match(
        "/BankController\.restore[\s\S]*?only:\s*\[\s*'rows'\s*,\s*'flash'\s*\]/",
        $source
    ))->toBe(1)
        ->and(preg_match(
            "/BankController\.restore[\s\S]*?only:\s*\[\s*'rows'\s*\]/",
            $source
        ))->toBe(0);
});
