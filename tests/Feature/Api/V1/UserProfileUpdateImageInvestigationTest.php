<?php

/**
 * POST /api/v1/user/auth/profile/update — partial (PATCH-style) profile updates.
 */
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Geo\Models\Nationality;

function profileUpdateNationality(): Nationality
{
    return Nationality::query()->create([
        'code' => 'SA',
        'is_active' => true,
        'translations' => geoNameTranslations('Saudi'),
    ]);
}

function authenticatedProfileUser(?Nationality $nationality = null): User
{
    $nationality ??= profileUpdateNationality();

    $user = User::factory()->create([
        'f_name' => 'Ada',
        'l_name' => 'Lovelace',
        'email' => 'ada.profile@example.com',
        'phone' => '512345678',
        'nationality_id' => $nationality->id,
        'image' => 'users/old-avatar.jpg',
        'password' => 'old-password-secret',
    ]);

    Sanctum::actingAs($user, ['*'], 'user-api');

    return $user;
}

test('user can update only their name without providing password or other fields', function (): void {
    $user = authenticatedProfileUser();

    $this->post('/api/v1/user/auth/profile/update', [
        'f_name' => 'Augusta',
        'l_name' => 'Byron',
    ], [
        'Accept' => 'application/json',
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.f_name', 'Augusta')
        ->assertJsonPath('data.l_name', 'Byron')
        ->assertJsonPath('data.email', 'ada.profile@example.com');

    $fresh = $user->fresh();
    expect($fresh->f_name)->toBe('Augusta')
        ->and($fresh->l_name)->toBe('Byron')
        ->and($fresh->email)->toBe('ada.profile@example.com')
        ->and($fresh->phone)->toBe('512345678')
        ->and($fresh->nationality_id)->toBe($user->nationality_id)
        ->and($fresh->image)->toBe('users/old-avatar.jpg')
        ->and(Hash::check('old-password-secret', $fresh->password))->toBeTrue();
});

test('user can update only their profile image without providing password or other fields', function (): void {
    Storage::fake('public');
    $user = authenticatedProfileUser();
    Storage::disk('public')->put('users/old-avatar.jpg', 'old');

    $this->post('/api/v1/user/auth/profile/update', [
        'image' => UploadedFile::fake()->image('avatar.jpg'),
    ], [
        'Accept' => 'application/json',
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $fresh = $user->fresh();
    expect($fresh->image)->not->toBe('users/old-avatar.jpg')
        ->and($fresh->image)->toStartWith('users/')
        ->and(Storage::disk('public')->exists($fresh->image))->toBeTrue()
        ->and(Storage::disk('public')->exists('users/old-avatar.jpg'))->toBeFalse()
        ->and($fresh->f_name)->toBe('Ada')
        ->and($fresh->email)->toBe('ada.profile@example.com')
        ->and(Hash::check('old-password-secret', $fresh->password))->toBeTrue();
});

test('user can update their password when providing password + confirmation', function (): void {
    $user = authenticatedProfileUser();

    $this->post('/api/v1/user/auth/profile/update', [
        'password' => 'new-password-secret',
        'password_confirmation' => 'new-password-secret',
    ], [
        'Accept' => 'application/json',
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $fresh = $user->fresh();
    expect(Hash::check('new-password-secret', $fresh->password))->toBeTrue()
        ->and(Hash::check('old-password-secret', $fresh->password))->toBeFalse()
        ->and($fresh->f_name)->toBe('Ada')
        ->and($fresh->email)->toBe('ada.profile@example.com');
});

test('password confirmation mismatch still returns a validation error when password is provided', function (): void {
    $user = authenticatedProfileUser();

    $this->post('/api/v1/user/auth/profile/update', [
        'password' => 'new-password-secret',
        'password_confirmation' => 'does-not-match',
    ], [
        'Accept' => 'application/json',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);

    expect(Hash::check('old-password-secret', $user->fresh()->password))->toBeTrue();
});

it('accepts full profile fields plus image without requiring a password change', function (): void {
    Storage::fake('public');
    $user = authenticatedProfileUser();
    Storage::disk('public')->put('users/old-avatar.jpg', 'old');

    $this->post('/api/v1/user/auth/profile/update', [
        'f_name' => $user->f_name,
        'l_name' => $user->l_name,
        'email' => $user->email,
        'phone' => $user->phone,
        'nationality_id' => $user->nationality_id,
        'image' => UploadedFile::fake()->image('avatar.jpg'),
    ], [
        'Accept' => 'application/json',
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $fresh = $user->fresh();
    expect($fresh->image)->not->toBe('users/old-avatar.jpg')
        ->and(Storage::disk('public')->exists($fresh->image))->toBeTrue()
        ->and(Hash::check('old-password-secret', $fresh->password))->toBeTrue();
});

it('rejects oversized images on partial profile update', function (): void {
    Storage::fake('public');
    $user = authenticatedProfileUser();

    $this->post('/api/v1/user/auth/profile/update', [
        'image' => UploadedFile::fake()->image('huge.jpg')->size(3072),
    ], [
        'Accept' => 'application/json',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);

    expect($user->fresh()->image)->toBe('users/old-avatar.jpg');
});
