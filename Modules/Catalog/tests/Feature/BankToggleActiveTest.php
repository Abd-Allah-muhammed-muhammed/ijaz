<?php

use Modules\Catalog\Http\Controllers\Dashboard\BankController;
use Modules\Catalog\Models\Bank;

/**
 * Quick Active/Inactive toggle for Banks — single-field flip, independent of soft-delete/restore.
 */
test('a dedicated toggle endpoint flips is_active without requiring the full update payload/validation', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit banks']);
    $bank = Bank::factory()->create([
        'is_active' => true,
        'translations' => geoNameTranslations('ToggleActiveBank'),
    ]);

    $this->actingAs($admin, 'admin')
        ->patch(action([BankController::class, 'toggleActive'], $bank))
        ->assertRedirect()
        ->assertSessionHas('success', __('data saved successfully'));

    expect($bank->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin, 'admin')
        ->patch(action([BankController::class, 'toggleActive'], $bank))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($bank->fresh()->is_active)->toBeTrue();
});

test('deleting a bank does not change its is_active value — regression, explicit proof', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete banks']);

    $active = Bank::factory()->create([
        'is_active' => true,
        'translations' => geoNameTranslations('DeleteKeepsActive'),
    ]);
    $inactive = Bank::factory()->inactive()->create([
        'translations' => geoNameTranslations('DeleteKeepsInactive'),
    ]);

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $active))
        ->assertRedirect(route('dashboard.banks.index'));

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $inactive))
        ->assertRedirect(route('dashboard.banks.index'));

    expect(Bank::withTrashed()->find($active->id)?->is_active)->toBeTrue()
        ->and(Bank::withTrashed()->find($inactive->id)?->is_active)->toBeFalse();
});

test('restoring a bank does not change its is_active value — regression, explicit proof, including when it was false before deletion', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete banks']);

    $wasActive = Bank::factory()->create([
        'is_active' => true,
        'translations' => geoNameTranslations('RestoreKeepsActive'),
    ]);
    $wasInactive = Bank::factory()->inactive()->create([
        'translations' => geoNameTranslations('RestoreKeepsInactive'),
    ]);

    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $wasActive));
    $this->actingAs($admin, 'admin')
        ->delete(action([BankController::class, 'destroy'], $wasInactive));

    $this->actingAs($admin, 'admin')
        ->post(action([BankController::class, 'restore'], $wasActive))
        ->assertSessionHas('success');
    $this->actingAs($admin, 'admin')
        ->post(action([BankController::class, 'restore'], $wasInactive))
        ->assertSessionHas('success');

    expect(Bank::find($wasActive->id)?->is_active)->toBeTrue()
        ->and(Bank::find($wasInactive->id)?->is_active)->toBeFalse()
        ->and(Bank::find($wasInactive->id)?->trashed())->toBeFalse();
});

test('toggle is permission-gated the same as edit', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $viewer = createGeoDashboardAdmin(['show banks']);
    $bank = Bank::factory()->create([
        'is_active' => true,
        'translations' => geoNameTranslations('TogglePermissionBank'),
    ]);

    $this->actingAs($viewer, 'admin')
        ->patch(action([BankController::class, 'toggleActive'], $bank))
        ->assertForbidden();

    expect($bank->fresh()->is_active)->toBeTrue();

    $editor = createGeoDashboardAdmin(['edit banks']);

    $this->actingAs($editor, 'admin')
        ->patch(action([BankController::class, 'toggleActive'], $bank))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($bank->fresh()->is_active)->toBeFalse();
});

test('Banks Index row shows a quick Active/Inactive toggle alongside Edit/Delete', function (): void {
    $path = resource_path('js/apps/admin/pages/Banks/Index.tsx');
    expect(file_exists($path))->toBeTrue();

    $source = (string) file_get_contents($path);

    expect($source)->toContain('toggleActive')
        ->and($source)->toContain('FormCheck')
        ->and($source)->toContain('edit banks')
        ->and($source)->toContain('BankController.edit')
        ->and($source)->toContain('BankController.destroy');
});

test('clicking the toggle flips the status immediately with a success toast, without navigating to the edit form', function (): void {
    $path = resource_path('js/apps/admin/pages/Banks/Index.tsx');
    $source = (string) file_get_contents($path);

    expect(preg_match(
        "/router\.patch\(\s*BankController\.toggleActive[\s\S]*?only:\s*\[\s*'rows'\s*,\s*'flash'\s*\]/",
        $source
    ))->toBe(1, 'Expected patch toggleActive with flash-inclusive partial reload');

    // Must not navigate to the edit form from the toggle handler.
    expect(preg_match(
        "/toggleActive[\s\S]{0,400}BankController\.edit/",
        $source
    ))->toBe(0, 'Toggle must not navigate to edit');
});
