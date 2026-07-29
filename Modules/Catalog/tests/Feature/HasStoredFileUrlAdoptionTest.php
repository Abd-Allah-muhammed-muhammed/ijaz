<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Http\Resources\Dashboard\DeviceCategoryResource;
use Modules\Catalog\Http\Resources\Dashboard\SpecializationResource;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\Specialization;

test('specialization dashboard resource icon_url matches previous Storage::url output', function () {
    Storage::fake('public');

    $path = 'specializations/icon-test.png';
    Storage::disk('public')->put($path, 'fake-icon');

    $specialization = Specialization::query()->create([
        'icon' => $path,
        'parent_id' => null,
    ]);
    $specialization->translateOrNew('en')->title = 'Engineering';
    $specialization->save();

    $expected = Storage::disk('public')->url($path);
    $resource = (new SpecializationResource($specialization->fresh()))->resolve();

    expect($specialization->icon_url)->toBe($expected)
        ->and($resource['icon'])->toBe($expected)
        ->and($resource['icon'])->not->toContain('ui-avatars');
});

test('stored file url falls back to placeholder when path is set but file is missing', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'image' => 'users/missing-avatar.png',
    ]);

    expect(Storage::disk('public')->exists('users/missing-avatar.png'))->toBeFalse()
        ->and($user->image_url)->toBe(asset('media/avatars/blank.png'));
});

test('device category with no icon returns null, not a placeholder', function () {
    $category = DeviceCategory::query()->create([
        'icon' => null,
        'parent_id' => null,
    ]);
    $category->translateOrNew('en')->title = 'Phones';
    $category->save();

    $resource = (new DeviceCategoryResource($category->fresh()))->resolve();

    expect($category->icon_url)->toBeNull()
        ->and($resource['icon'])->toBeNull()
        ->and($resource['icon'])->not->toBeString();
});
