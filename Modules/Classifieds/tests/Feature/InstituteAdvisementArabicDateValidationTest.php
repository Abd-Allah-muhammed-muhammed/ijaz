<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Modules\Catalog\Models\Specialization;
use Modules\Classifieds\Enums\InstituteTypeEnum;
use Modules\Classifieds\Enums\StudyTypeEnum;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

/**
 * Convert Western ASCII digits in a string to Arabic-Indic digits (U+0660–U+0669).
 */
function toArabicIndicDigits(string $value): string
{
    return strtr($value, [
        '0' => '٠',
        '1' => '١',
        '2' => '٢',
        '3' => '٣',
        '4' => '٤',
        '5' => '٥',
        '6' => '٦',
        '7' => '٧',
        '8' => '٨',
        '9' => '٩',
    ]);
}

test('institute advisement accepts registration/study dates typed in Arabic-Indic digits', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $specialization = Specialization::factory()->create();

    $registrationStart = now()->addDays(7)->toDateString();
    $registrationEnd = now()->addDays(14)->toDateString();
    $studyStart = now()->addDays(21)->toDateString();
    $studyEnd = now()->addDays(60)->toDateString();

    $this->postJson('/api/v1/classifieds/institutes', [
        'title' => 'Arabic Date Course',
        'description' => 'Course created with Arabic-Indic date digits',
        'type' => InstituteTypeEnum::INSTITUTE->value,
        'study_type' => StudyTypeEnum::ONSITE->value,
        'specialization_id' => $specialization->id,
        'city_id' => $city->id,
        'region_id' => $region->id,
        'registration_start' => toArabicIndicDigits($registrationStart),
        'registration_end' => toArabicIndicDigits($registrationEnd),
        'study_start' => toArabicIndicDigits($studyStart),
        'study_end' => toArabicIndicDigits($studyEnd),
    ])
        ->assertSuccessful()
        ->assertJsonMissingValidationErrors([
            'registration_start',
            'registration_end',
            'study_start',
            'study_end',
        ]);

    $advisement = InstituteAdvisement::query()->where('title', 'Arabic Date Course')->firstOrFail();

    expect($advisement->registration_start?->toDateString())->toBe($registrationStart)
        ->and($advisement->registration_end?->toDateString())->toBe($registrationEnd)
        ->and($advisement->study_start?->toDateString())->toBe($studyStart)
        ->and($advisement->study_end?->toDateString())->toBe($studyEnd);
});
