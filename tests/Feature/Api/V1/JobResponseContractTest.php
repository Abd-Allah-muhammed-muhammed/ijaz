<?php

use App\Enums\Jobs\JobTypeEnum;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;

/**
 * Response-shape contract lock for mobile Jobs API.
 * Written against the pre-extraction controller; must keep passing after Modules/Jobs move.
 */
function createJobContractFixtures(): array
{
    $user = User::factory()->create([
        'f_name' => 'Contract',
        'l_name' => 'User',
        'phone' => '0501112233',
    ]);
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $nationality = Nationality::query()->create([
        'code' => 'SA',
        'is_active' => true,
        'translations' => [
            'en' => ['name' => 'Saudi'],
            'ar' => ['name' => 'سعودي'],
            'ur' => ['name' => 'Saudi UR'],
            'hi' => ['name' => 'Saudi HI'],
        ],
    ]);

    return compact('user', 'region', 'city', 'nationality');
}

/**
 * @return array<string, mixed>
 */
function jobContractPayload(array $fixtures, string $title = 'Contract Job'): array
{
    return [
        'title' => $title,
        'description' => 'Contract description',
        'expected_salary' => 5000,
        'expired_at' => now()->addDays(10)->toDateString(),
        'contact_number' => '0501234567',
        'city_id' => $fixtures['city']->id,
        'region_id' => $fixtures['region']->id,
        'nationality_id' => $fixtures['nationality']->id,
        'type' => JobTypeEnum::Private->value,
    ];
}

/**
 * @param  array<string, mixed>  $json
 * @return list<string>
 */
function jobContractKeys(array $json): array
{
    $keys = [];
    $walk = function ($value, string $prefix = '') use (&$walk, &$keys): void {
        if (! is_array($value)) {
            $keys[] = $prefix;

            return;
        }

        if ($value === []) {
            $keys[] = $prefix.'[]';

            return;
        }

        $isList = array_is_list($value);
        foreach ($value as $k => $v) {
            $path = $isList ? $prefix.'.*' : ($prefix === '' ? (string) $k : $prefix.'.'.$k);
            if (is_array($v)) {
                $walk($v, $path);
            } else {
                $keys[] = $path;
            }
        }
    };
    $walk($json);

    return array_values(array_unique($keys));
}

test('jobs index response shape contract', function () {
    $fixtures = createJobContractFixtures();
    Sanctum::actingAs($fixtures['user']);

    $fixtures['user']->jobs()->create([
        ...jobContractPayload($fixtures),
        'expired_at' => now()->addDays(10),
        'type' => JobTypeEnum::Private,
    ]);

    $response = $this->getJson('/api/v1/jobs');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys([
            'items', 'total', 'count', 'per_page', 'current_page', 'last_page', 'has_more_pages',
        ])
        ->and($json['data']['items'])->toBeArray()->not->toBeEmpty();

    $item = $json['data']['items'][0];
    expect($item)->toHaveKeys([
        'id', 'title', 'description', 'expected_salary', 'expired_at', 'contact_number', 'created_at',
        'city_id', 'region_id', 'nationality_id', 'type', 'skills',
    ]);
    // index does not eager-load media — key absent unless loaded (preserve current behavior).
    expect($item)->not->toHaveKey('media');
});

test('jobs show response shape contract', function () {
    $fixtures = createJobContractFixtures();
    Sanctum::actingAs($fixtures['user']);

    $job = $fixtures['user']->jobs()->create([
        ...jobContractPayload($fixtures, 'Show Contract Job'),
        'expired_at' => now()->addDays(10),
        'type' => JobTypeEnum::Private,
    ]);

    $response = $this->getJson('/api/v1/jobs/'.$job->id);
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys([
            'id', 'title', 'description', 'expected_salary', 'expired_at', 'contact_number', 'created_at',
            'city_id', 'region_id', 'nationality_id', 'type', 'skills', 'media',
        ]);
});

test('jobs store response shape contract via POST with fixed phone validation', function () {
    Storage::fake('public');
    $fixtures = createJobContractFixtures();
    Sanctum::actingAs($fixtures['user']);

    $response = $this->postJson('/api/v1/jobs', jobContractPayload($fixtures, 'Store Via Post'));
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys([
            'id', 'title', 'description', 'expected_salary', 'expired_at', 'contact_number', 'created_at',
            'city_id', 'region_id', 'nationality_id', 'type', 'skills', 'media',
        ])
        ->and($json['data']['title'])->toBe('Store Via Post');
});
