<?php

test('lang/ar.json "name in hi" translates to a caption referring to Hindi (بالهندية), not Urdu', function () {
    $ar = json_decode((string) file_get_contents(lang_path('ar.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($ar['name in hi'])->toBe('الاسم بالهندية')
        ->and($ar['name in hi'])->toContain('بالهندية')
        ->and($ar['name in hi'])->not->toContain('بالاردو');

    app()->setLocale('ar');
    expect(__('name in hi'))->toBe('الاسم بالهندية');

    expect($ar['title in hi'])->toBe('العنوان بالهندية')
        ->and($ar['title in ur'])->toBe('العنوان بالاردو');
});

test('lang/ar.json "name in ur" translates to a caption referring to Urdu (بالاردو), not Hindi', function () {
    $ar = json_decode((string) file_get_contents(lang_path('ar.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($ar['name in ur'])->toBe('الاسم بالاردو')
        ->and($ar['name in ur'])->toContain('بالاردو')
        ->and($ar['name in ur'])->not->toContain('بالهندية');

    app()->setLocale('ar');
    expect(__('name in ur'))->toBe('الاسم بالاردو');
});
