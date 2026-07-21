<?php

use App\Enums\Jobs\JobTypeEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Sanctum\Sanctum;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;
use Modules\Jobs\Models\JobOffer;

test('HasJobs jobs relation returns MorphMany and behaves identically for index/store', function () {
    $user = User::factory()->create();
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $nationality = Nationality::query()->create([
        'code' => 'SA',
        'is_active' => true,
    ]);

    expect($user->jobs())->toBeInstanceOf(MorphMany::class);

    $payload = fn (string $title, float $salary) => [
        'title' => $title,
        'description' => 'Desc',
        'expected_salary' => $salary,
        'expired_at' => now()->addDays(10),
        'contact_number' => '0501234567',
        'city_id' => $city->id,
        'region_id' => $region->id,
        'nationality_id' => $nationality->id,
        'type' => JobTypeEnum::Private,
    ];

    // Same create path JobController::store uses after validation.
    $first = $user->jobs()->create($payload('First offer', 1000));
    $second = $user->jobs()->create($payload('Second offer', 2000));

    expect(JobOffer::query()->where('user_id', $user->id)->where('user_type', User::class)->count())->toBe(2)
        ->and($user->jobs()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$first->id, $second->id])->sort()->values()->all())
        ->and($user->jobs)->toHaveCount(2);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/jobs')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data.items');

    $third = $user->jobs()->create($payload('Third offer', 3000));

    expect($user->jobs()->count())->toBe(3)
        ->and($third->title)->toBe('Third offer');

    $this->getJson('/api/v1/jobs')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data.items');
});
