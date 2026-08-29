<?php

use Modules\Settings\Http\Controllers\Dashboard\SettingController;
use Modules\Settings\Models\Setting;
use Modules\Settings\Models\SettingHistory;

beforeEach(function (): void {
    withoutSettingsDashboardLocaleMiddleware();
});

test('saving a percent-suffixed key (e.g. guarantee_fee_percent) with a non-numeric value is rejected with a clear validation error', function () {
    $admin = createSettingsDashboardAdmin(['edit settings']);

    Setting::query()->updateOrCreate(
        ['key' => 'guarantee_fee_percent'],
        ['content' => '2.5', 'group' => 'guarantor', 'is_public' => true],
    );

    $this->actingAs($admin, 'admin')
        ->from(action([SettingController::class, 'index']))
        ->put(action([SettingController::class, 'update']), [
            'group' => 'guarantor',
            'values' => [
                'guarantee_fee_percent' => 'abc',
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('values.guarantee_fee_percent');

    expect(Setting::query()->where('key', 'guarantee_fee_percent')->value('content'))->toBe('2.5');
});

test('saving a percent-suffixed key outside 0-100 is rejected', function () {
    $admin = createSettingsDashboardAdmin(['edit settings']);

    Setting::query()->updateOrCreate(
        ['key' => 'guarantee_fee_percent'],
        ['content' => '2.5', 'group' => 'guarantor', 'is_public' => true],
    );

    $this->actingAs($admin, 'admin')
        ->from(action([SettingController::class, 'index']))
        ->put(action([SettingController::class, 'update']), [
            'values' => ['guarantee_fee_percent' => '150'],
        ])
        ->assertSessionHasErrors('values.guarantee_fee_percent');

    $this->actingAs($admin, 'admin')
        ->from(action([SettingController::class, 'index']))
        ->put(action([SettingController::class, 'update']), [
            'values' => ['guarantee_fee_percent' => '-1'],
        ])
        ->assertSessionHasErrors('values.guarantee_fee_percent');
});

test('saving a fees/amount-suffixed key with a negative value is rejected', function () {
    $admin = createSettingsDashboardAdmin(['edit settings']);

    Setting::query()->updateOrCreate(
        ['key' => 'testing_fees'],
        ['content' => '15', 'group' => 'payment', 'is_public' => false],
    );
    Setting::query()->updateOrCreate(
        ['key' => 'min_withdraw_amount'],
        ['content' => '200', 'group' => 'wallet', 'is_public' => true],
    );

    $this->actingAs($admin, 'admin')
        ->from(action([SettingController::class, 'index']))
        ->put(action([SettingController::class, 'update']), [
            'values' => ['testing_fees' => '-5'],
        ])
        ->assertSessionHasErrors('values.testing_fees');

    $this->actingAs($admin, 'admin')
        ->from(action([SettingController::class, 'index']))
        ->put(action([SettingController::class, 'update']), [
            'values' => ['min_withdraw_amount' => '-1'],
        ])
        ->assertSessionHasErrors('values.min_withdraw_amount');
});

test('saving a days/hours-suffixed key with a non-integer or negative value is rejected', function () {
    $admin = createSettingsDashboardAdmin(['edit settings']);

    Setting::query()->updateOrCreate(
        ['key' => 'order_dispute_window_hours'],
        ['content' => '48', 'group' => 'wallet', 'is_public' => true],
    );
    Setting::query()->updateOrCreate(
        ['key' => 'guarantor_first_installment_max_days'],
        ['content' => '5', 'group' => 'guarantor', 'is_public' => false],
    );

    $this->actingAs($admin, 'admin')
        ->from(action([SettingController::class, 'index']))
        ->put(action([SettingController::class, 'update']), [
            'values' => ['order_dispute_window_hours' => '12.5'],
        ])
        ->assertSessionHasErrors('values.order_dispute_window_hours');

    $this->actingAs($admin, 'admin')
        ->from(action([SettingController::class, 'index']))
        ->put(action([SettingController::class, 'update']), [
            'values' => ['guarantor_first_installment_max_days' => '-2'],
        ])
        ->assertSessionHasErrors('values.guarantor_first_installment_max_days');
});

test('a setting key with no recognized suffix pattern still accepts a plain string, unaffected — regression, this must not become newly restrictive for free-text settings like offer_note', function () {
    $admin = createSettingsDashboardAdmin(['edit settings']);

    Setting::query()->updateOrCreate(
        ['key' => 'offer_note'],
        ['content' => 'old note', 'group' => 'general', 'is_public' => true, 'type' => 'textarea'],
    );

    $this->actingAs($admin, 'admin')
        ->put(action([SettingController::class, 'update']), [
            'group' => 'general',
            'values' => [
                'offer_note' => 'Any free text — even "abc" or "-50" is fine for notes.',
            ],
        ])
        ->assertRedirect();

    expect(Setting::query()->where('key', 'offer_note')->value('content'))
        ->toBe('Any free text — even "abc" or "-50" is fine for notes.');
});

test('updating a setting value creates a setting_histories row with the actor, old content, new content, and timestamp', function () {
    $admin = createSettingsDashboardAdmin(['edit settings']);

    Setting::query()->updateOrCreate(
        ['key' => 'phone'],
        ['content' => '966500000000', 'group' => 'general', 'is_public' => true],
    );

    $this->actingAs($admin, 'admin')
        ->put(action([SettingController::class, 'update']), [
            'values' => ['phone' => '966511111111'],
        ])
        ->assertRedirect();

    $history = SettingHistory::query()->where('key', 'phone')->latest('id')->first();

    expect($history)->not->toBeNull()
        ->and($history->old_content)->toBe('966500000000')
        ->and($history->new_content)->toBe('966511111111')
        ->and($history->admin_id)->toBe($admin->id)
        ->and($history->created_at)->not->toBeNull();

    // Unchanged value must not create a noise row
    $countBefore = SettingHistory::query()->where('key', 'phone')->count();

    $this->actingAs($admin, 'admin')
        ->put(action([SettingController::class, 'update']), [
            'values' => ['phone' => '966511111111'],
        ])
        ->assertRedirect();

    expect(SettingHistory::query()->where('key', 'phone')->count())->toBe($countBefore);
});

test('is_public can no longer be changed via the Dashboard update endpoint — sending is_public in the request is ignored/rejected, only readable via GET', function () {
    $admin = createSettingsDashboardAdmin(['edit settings', 'show settings']);

    Setting::query()->updateOrCreate(
        ['key' => 'phone'],
        ['content' => '966500000000', 'group' => 'general', 'is_public' => false],
    );

    $this->actingAs($admin, 'admin')
        ->put(action([SettingController::class, 'update']), [
            'values' => ['phone' => '966522222222'],
            'is_public' => ['phone' => true],
        ])
        ->assertRedirect();

    $setting = Setting::query()->where('key', 'phone')->first();

    expect($setting->content)->toBe('966522222222')
        ->and((bool) $setting->is_public)->toBeFalse();

    $this->actingAs($admin, 'admin')
        ->get(action([SettingController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('groups.general', fn ($rows) => collect($rows)->contains(
                fn ($row) => ($row['key'] ?? null) === 'phone'
                    && ($row['is_public'] ?? null) === false
            ))
        );
});

test('settings history endpoint returns past changes for a key', function () {
    $admin = createSettingsDashboardAdmin(['show settings', 'edit settings']);

    Setting::query()->updateOrCreate(
        ['key' => 'email'],
        ['content' => 'old@ijaz.sa', 'group' => 'general', 'is_public' => true],
    );

    $this->actingAs($admin, 'admin')
        ->put(action([SettingController::class, 'update']), [
            'values' => ['email' => 'new@ijaz.sa'],
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'admin')
        ->getJson(action([SettingController::class, 'history'], ['key' => 'email']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.old_content', 'old@ijaz.sa')
        ->assertJsonPath('data.0.new_content', 'new@ijaz.sa')
        ->assertJsonPath('data.0.actor.name', $admin->name);
});
