<?php

use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Http\Resources\Dashboard\DeviceCategoryResource;
use Modules\Catalog\Http\Resources\Dashboard\SpecializationResource;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\Specialization;

test('specialization dashboard resource icon_url matches previous Storage::url output', function () {
    $path = 'specializations/icon-test.png';

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
