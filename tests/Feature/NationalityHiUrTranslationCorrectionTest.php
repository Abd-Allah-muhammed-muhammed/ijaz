<?php

use Illuminate\Support\Facades\DB;
use Modules\Geo\Actions\Nationality\CorrectSwappedHiUrNationalityTranslationsAction;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\NationalityTranslation;

/**
 * @param  array{en: string, ar: string, hi: string, ur: string}  $names
 */
function seedNationalityForHiUrCorrection(int $id, array $names): Nationality
{
    $now = now();
    DB::table('nationalities')->insert([
        'id' => $id,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $nationality = Nationality::query()->findOrFail($id);
    $nationality->fill([
        'translations' => [
            'en' => ['name' => $names['en']],
            'ar' => ['name' => $names['ar']],
            'hi' => ['name' => $names['hi']],
            'ur' => ['name' => $names['ur']],
        ],
    ])->save();

    return $nationality;
}

function nationalityNameForHiUrCorrection(int $id, string $locale): string
{
    $name = NationalityTranslation::query()
        ->where('nationality_id', $id)
        ->where('locale', $locale)
        ->value('name');

    expect($name)->toBeString();

    return (string) $name;
}

test('after the fix, nationality id 25 (Pakistani) has Devanagari script in its hi translation and Arabic/Urdu script in its ur translation — not reversed', function () {
    seedNationalityForHiUrCorrection(25, [
        'en' => 'Pakistani',
        'ar' => 'باكستاني',
        'hi' => 'پاکستانی',
        'ur' => 'पाकिस्तानी',
    ]);

    $enBefore = nationalityNameForHiUrCorrection(25, 'en');
    $arBefore = nationalityNameForHiUrCorrection(25, 'ar');

    app(CorrectSwappedHiUrNationalityTranslationsAction::class)->handle();
    app(CorrectSwappedHiUrNationalityTranslationsAction::class)->handle();

    $hi = nationalityNameForHiUrCorrection(25, 'hi');
    $ur = nationalityNameForHiUrCorrection(25, 'ur');

    expect($hi)->toMatch('/\p{Devanagari}/u')
        ->and($hi)->not->toMatch('/\p{Arabic}/u')
        ->and($hi)->toBe('पाकिस्तानी')
        ->and($ur)->toMatch('/\p{Arabic}/u')
        ->and($ur)->not->toMatch('/\p{Devanagari}/u')
        ->and($ur)->toBe('پاکستانی')
        ->and(nationalityNameForHiUrCorrection(25, 'en'))->toBe($enBefore)
        ->and(nationalityNameForHiUrCorrection(25, 'ar'))->toBe($arBefore);
});

test('nationality ids 1, 2, and 17 are unchanged by this migration — their hi/ur values remain exactly as they were before', function () {
    $unchanged = [
        1 => ['en' => 'Saudi', 'ar' => 'سعودي', 'hi' => 'सऊदी', 'ur' => 'سعودي'],
        2 => ['en' => 'Egyptian', 'ar' => 'مصري', 'hi' => 'मिस्र', 'ur' => 'مِصْری'],
        17 => ['en' => 'Moroccan', 'ar' => 'مغربي', 'hi' => 'मोरक्कन', 'ur' => 'مراکشی'],
    ];

    foreach ($unchanged as $id => $names) {
        seedNationalityForHiUrCorrection($id, $names);
    }

    $before = [];
    foreach (array_keys($unchanged) as $id) {
        $before[$id] = [
            'en' => nationalityNameForHiUrCorrection($id, 'en'),
            'ar' => nationalityNameForHiUrCorrection($id, 'ar'),
            'hi' => nationalityNameForHiUrCorrection($id, 'hi'),
            'ur' => nationalityNameForHiUrCorrection($id, 'ur'),
        ];
    }

    app(CorrectSwappedHiUrNationalityTranslationsAction::class)->handle();

    foreach ($before as $id => $names) {
        expect(nationalityNameForHiUrCorrection($id, 'hi'))->toBe($names['hi'])
            ->and(nationalityNameForHiUrCorrection($id, 'ur'))->toBe($names['ur'])
            ->and(nationalityNameForHiUrCorrection($id, 'en'))->toBe($names['en'])
            ->and(nationalityNameForHiUrCorrection($id, 'ar'))->toBe($names['ar']);
    }
});

test('nationality id 33 (Filipino) has real Hindi text in hi (not Urdu script) and real Urdu text in ur (not the English word "Filipino") after the fix', function () {
    seedNationalityForHiUrCorrection(33, [
        'en' => 'Filipino',
        'ar' => 'فلبيني',
        'hi' => 'فلپائنی',
        'ur' => 'Filipino',
    ]);

    $enBefore = nationalityNameForHiUrCorrection(33, 'en');
    $arBefore = nationalityNameForHiUrCorrection(33, 'ar');

    app(CorrectSwappedHiUrNationalityTranslationsAction::class)->handle();
    app(CorrectSwappedHiUrNationalityTranslationsAction::class)->handle();

    $hi = nationalityNameForHiUrCorrection(33, 'hi');
    $ur = nationalityNameForHiUrCorrection(33, 'ur');

    expect($hi)->toBe(CorrectSwappedHiUrNationalityTranslationsAction::FILIPINO_HI_NAME)
        ->and($hi)->toMatch('/\p{Devanagari}/u')
        ->and($hi)->not->toMatch('/\p{Arabic}/u')
        ->and($ur)->toBe(CorrectSwappedHiUrNationalityTranslationsAction::FILIPINO_UR_NAME)
        ->and($ur)->toMatch('/\p{Arabic}/u')
        ->and($ur)->not->toBe('Filipino')
        ->and(nationalityNameForHiUrCorrection(33, 'en'))->toBe($enBefore)
        ->and(nationalityNameForHiUrCorrection(33, 'ar'))->toBe($arBefore);
});
