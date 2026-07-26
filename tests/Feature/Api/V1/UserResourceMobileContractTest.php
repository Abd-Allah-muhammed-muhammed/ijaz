<?php

use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Geo\Models\Nationality;

/**
 * Contract-freeze tests for the mobile-facing UserResource
 * (App\Http\Resources\Api\V1\User\UserResource) BEFORE consolidating it with
 * the flat App\Http\Resources\Api\V1\UserResource duplicate.
 *
 * These lock the EXACT current JSON output of every mobile endpoint that
 * serializes a User through this resource — key set, key order, value types,
 * and the presence/absence of the conditional `nationality` object depending
 * on each path's eager loading. Any consolidation must keep these green.
 */

/** @return list<string> Exact UserResource keys when `nationality` is NOT eager loaded. */
function userResourceKeysWithoutNationality(): array
{
    return [
        'id', 'socket_id', 'name', 'f_name', 'l_name', 'phone', 'image',
        'language', 'latitude', 'longitude', 'email', 'nationality_id',
    ];
}

/** @return list<string> Exact UserResource keys when `nationality` IS eager loaded. */
function userResourceKeysWithNationality(): array
{
    return [...userResourceKeysWithoutNationality(), 'nationality'];
}

function createUserResourceNationality(): Nationality
{
    return Nationality::query()->create([
        'code' => 'SA',
        'is_active' => true,
        'translations' => geoNameTranslations('Saudi'),
    ]);
}

test('me endpoint freezes UserResource shape without nationality eager load', function () {
    $nationality = createUserResourceNationality();

    $user = User::factory()->create([
        'phone' => '966512345678',
        'nationality_id' => $nationality->id,
        'latitude' => '24.7136',
        'longitude' => '46.6753',
    ]);

    Sanctum::actingAs($user, ['*'], 'user-api');

    $response = $this->getJson('/api/v1/user/auth/me')->assertOk();
    $json = $response->json();

    // Envelope contract (mmae/apiresponse makeResponse) — exact keys and values.
    expect(array_keys($json))->toBe(['success', 'data', 'errors', 'message', 'token'])
        ->and($json['success'])->toBeTrue()
        ->and($json['errors'])->toBe([])
        ->and($json['message'])->toBe('')
        ->and($json['token'])->toBe('');

    // Exact key set AND order — `nationality` must be ABSENT (no eager load on this path).
    expect(array_keys($json['data']))->toBe(userResourceKeysWithoutNationality());

    $response->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.socket_id', 'user-'.$user->id)
        ->assertJsonPath('data.name', $user->f_name.' '.$user->l_name)
        ->assertJsonPath('data.f_name', $user->f_name)
        ->assertJsonPath('data.l_name', $user->l_name)
        ->assertJsonPath('data.phone', '966512345678')
        ->assertJsonPath('data.image', $user->image_url)
        ->assertJsonPath('data.language', 'en')
        ->assertJsonPath('data.latitude', $user->fresh()->latitude)
        ->assertJsonPath('data.longitude', $user->fresh()->longitude)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.nationality_id', $nationality->id);

    // Value-type contract for the mobile client.
    expect($json['data']['id'])->toBeInt()
        ->and($json['data']['socket_id'])->toBeString()
        ->and($json['data']['name'])->toBeString()
        ->and($json['data']['f_name'])->toBeString()
        ->and($json['data']['l_name'])->toBeString()
        ->and($json['data']['phone'])->toBeString()
        ->and($json['data']['image'])->toBeString()
        ->and($json['data']['language'])->toBeString()
        ->and($json['data']['email'])->toBeString()
        ->and($json['data']['nationality_id'])->toBeInt();
});

test('otp verify login freezes UserResource shape with nested nationality object', function () {
    $nationality = createUserResourceNationality();

    $user = User::factory()->create([
        'phone' => '966512345678',
        'nationality_id' => $nationality->id,
    ]);
    $user->updateOrCreateVerificationCode('1234', 'login');

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/otp/verify', [
        'type' => 'login',
        'otp' => '1234',
    ])->assertOk();

    $json = $response->json();

    expect(array_keys($json))->toBe(['success', 'data', 'errors', 'message', 'token'])
        ->and($json['success'])->toBeTrue()
        ->and($json['errors'])->toBe([])
        ->and($json['message'])->toBe('')
        ->and($json['token'])->toBeString()->not->toBeEmpty();

    // `nationality` MUST be present — nationality.translation is loaded on this path.
    expect(array_keys($json['data']))->toBe(userResourceKeysWithNationality())
        ->and(array_keys($json['data']['nationality']))->toBe(['id', 'name']);

    $response->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.socket_id', 'user-'.$user->id)
        ->assertJsonPath('data.nationality_id', $nationality->id)
        ->assertJsonPath('data.nationality.id', $nationality->id)
        ->assertJsonPath('data.nationality.name', 'Saudi EN');
});

test('register freezes UserResource shape with nested nationality object', function () {
    Storage::fake('public');
    // .env pins SMS_DRIVER=orbit (real gateway); use the module's TestingGateway so
    // registration's OTP dispatch never leaves the process (same precedent as SmsServiceTest).
    config(['sms.default' => 'testing']);

    $nationality = createUserResourceNationality();
    $normalizedPhone = Phone::make('512345678')->toString();

    $response = $this->postJson('/api/v1/user/auth/register', [
        'f_name' => 'Jane',
        'l_name' => 'Doe',
        'email' => 'jane.register@example.com',
        'phone' => '512345678',
        'nationality_id' => $nationality->id,
        'image' => UploadedFile::fake()->image('avatar.jpg'),
        'latitude' => '24.7136',
        'longitude' => '46.6753',
        'password' => null,
    ])->assertOk();

    $json = $response->json();

    expect(array_keys($json))->toBe(['success', 'data', 'errors', 'message', 'token'])
        ->and($json['success'])->toBeTrue()
        ->and($json['errors'])->toBe([])
        ->and($json['message'])->toBe('')
        ->and($json['token'])->toBeString()->not->toBeEmpty();

    expect(array_keys($json['data']))->toBe(userResourceKeysWithNationality())
        ->and(array_keys($json['data']['nationality']))->toBe(['id', 'name']);

    $response->assertJsonPath('data.name', 'Jane Doe')
        ->assertJsonPath('data.f_name', 'Jane')
        ->assertJsonPath('data.l_name', 'Doe')
        ->assertJsonPath('data.phone', $normalizedPhone)
        ->assertJsonPath('data.email', 'jane.register@example.com')
        ->assertJsonPath('data.nationality_id', $nationality->id)
        ->assertJsonPath('data.nationality.name', 'Saudi EN');

    expect($json['data']['id'])->toBeInt()
        ->and($json['data']['socket_id'])->toBe('user-'.$json['data']['id'])
        ->and($json['data']['image'])->toBeString()->toContain('users/');
});

test('profile update freezes UserResource shape with nested nationality object', function () {
    $nationality = createUserResourceNationality();

    $user = User::factory()->create([
        'phone' => '966512345678',
        'nationality_id' => $nationality->id,
    ]);

    Sanctum::actingAs($user, ['*'], 'user-api');

    $response = $this->postJson('/api/v1/user/auth/profile/update', [
        'f_name' => 'Updated',
        'l_name' => 'Person',
        'email' => 'updated.person@example.com',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
        'phone' => '0512345678',
        'nationality_id' => $nationality->id,
    ])->assertOk();

    $json = $response->json();

    expect(array_keys($json))->toBe(['success', 'data', 'errors', 'message', 'token'])
        ->and($json['success'])->toBeTrue()
        ->and($json['errors'])->toBe([])
        ->and($json['message'])->toBe('')
        ->and($json['token'])->toBe('');

    // `nationality` MUST be present — profileUpdate loads nationality.translation.
    expect(array_keys($json['data']))->toBe(userResourceKeysWithNationality())
        ->and(array_keys($json['data']['nationality']))->toBe(['id', 'name']);

    $response->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', 'Updated Person')
        ->assertJsonPath('data.f_name', 'Updated')
        ->assertJsonPath('data.l_name', 'Person')
        // profileUpdate stores the phone as submitted (no normalization on this path).
        ->assertJsonPath('data.phone', '0512345678')
        ->assertJsonPath('data.email', 'updated.person@example.com')
        ->assertJsonPath('data.nationality.name', 'Saudi EN');
});
