<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;
use Modules\Jobs\Enums\JobTypeEnum;
use Modules\Jobs\Models\JobOffer;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\Skill;

function createJobsModuleFixtures(): array
{
    $user = User::factory()->create();
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $nationality = Nationality::query()->create([
        'code' => 'KW',
        'is_active' => true,
        'translations' => [
            'en' => ['name' => 'Kuwaiti'],
            'ar' => ['name' => 'كويتي'],
            'ur' => ['name' => 'Kuwaiti UR'],
            'hi' => ['name' => 'Kuwaiti HI'],
        ],
    ]);
    $category = Category::factory()->create();
    $skill = Skill::query()->create(['category_id' => $category->id]);
    $skill->translations()->create([
        'locale' => 'en',
        'title' => 'PHP',
        'normalized_title' => 'php',
    ]);

    return compact('user', 'region', 'city', 'nationality', 'skill');
}

/**
 * @return array<string, mixed>
 */
function jobsModulePayload(array $fixtures, array $overrides = []): array
{
    return array_merge([
        'title' => 'Module Job',
        'description' => 'Desc',
        'expected_salary' => 3000,
        'expired_at' => now()->addDays(10)->toDateString(),
        'contact_number' => '0501234567',
        'city_id' => $fixtures['city']->id,
        'region_id' => $fixtures['region']->id,
        'nationality_id' => $fixtures['nationality']->id,
        'type' => JobTypeEnum::Private->value,
    ], $overrides);
}

test('store attaches skills and accepts a real phone contact_number', function () {
    $fixtures = createJobsModuleFixtures();
    Sanctum::actingAs($fixtures['user']);

    $response = $this->postJson('/api/v1/jobs', jobsModulePayload($fixtures, [
        'skills' => [$fixtures['skill']->id],
        'title' => 'Job With Skills',
    ]));

    $response->assertSuccessful()
        ->assertJsonPath('data.title', 'Job With Skills');

    $job = JobOffer::query()->where('title', 'Job With Skills')->firstOrFail();
    expect($job->skills()->pluck('skills.id')->all())->toBe([$fixtures['skill']->id])
        ->and($job->contact_number)->toBe('966501234567');
});

test('job store rejects an invalid contact_number', function () {
    $fixtures = createJobsModuleFixtures();
    Sanctum::actingAs($fixtures['user']);

    $this->postJson('/api/v1/jobs', jobsModulePayload($fixtures, [
        'contact_number' => 'not-a-phone',
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors(['contact_number']);
});

test('job expired_at accepts Arabic-Indic digits', function () {
    $fixtures = createJobsModuleFixtures();
    Sanctum::actingAs($fixtures['user']);

    $expiredAt = now()->addDays(10)->toDateString();
    $arabicExpiredAt = strtr($expiredAt, [
        '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
        '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
    ]);

    $this->postJson('/api/v1/jobs', jobsModulePayload($fixtures, [
        'title' => 'Job Arabic Date',
        'expired_at' => $arabicExpiredAt,
    ]))
        ->assertSuccessful()
        ->assertJsonMissingValidationErrors(['expired_at']);

    $job = JobOffer::query()->where('title', 'Job Arabic Date')->firstOrFail();
    expect($job->expired_at->toDateString())->toBe($expiredAt);
});

test('update syncs skills and rejects non-owners', function () {
    $fixtures = createJobsModuleFixtures();
    $other = User::factory()->create();
    Sanctum::actingAs($fixtures['user']);

    $job = $fixtures['user']->jobs()->create([
        ...jobsModulePayload($fixtures),
        'expired_at' => now()->addDays(10),
        'type' => JobTypeEnum::Private,
    ]);

    $this->putJson('/api/v1/jobs/'.$job->id, jobsModulePayload($fixtures, [
        'title' => 'Updated Job',
        'skills' => [$fixtures['skill']->id],
    ]))->assertSuccessful();

    expect($job->fresh()->title)->toBe('Updated Job')
        ->and($job->skills()->pluck('skills.id')->all())->toBe([$fixtures['skill']->id]);

    Sanctum::actingAs($other);
    $this->putJson('/api/v1/jobs/'.$job->id, jobsModulePayload($fixtures, [
        'title' => 'Hijack',
    ]))->assertNotFound();
});

test('destroy rejects non-owners', function () {
    $fixtures = createJobsModuleFixtures();
    $other = User::factory()->create();
    $job = $fixtures['user']->jobs()->create([
        ...jobsModulePayload($fixtures),
        'expired_at' => now()->addDays(10),
        'type' => JobTypeEnum::Private,
    ]);

    Sanctum::actingAs($other);
    $this->deleteJson('/api/v1/jobs/'.$job->id)->assertNotFound();
    expect(JobOffer::query()->whereKey($job->id)->exists())->toBeTrue();

    Sanctum::actingAs($fixtures['user']);
    $this->deleteJson('/api/v1/jobs/'.$job->id)->assertSuccessful();
    expect(JobOffer::query()->whereKey($job->id)->exists())->toBeFalse();
});
