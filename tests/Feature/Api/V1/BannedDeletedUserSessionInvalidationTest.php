<?php

/**
 * Session invalidation for banned/deleted users — belt (revoke + OTP gate) and
 * suspenders (per-request EnsureUserIsActive middleware).
 */
use App\Enums\Auth\OtpPurposeEnum;
use App\Enums\Users\UserStatusEnum;
use App\Models\Admin;
use App\Models\User;
use App\Services\Auth\UserAuthService;
use App\Support\Phone;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    config(['sms.default' => 'testing']);
});

function bannedSessionAdmin(array $permissions): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ], [
            'group' => 'users',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Ban Session Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $admin->givePermissionTo($permissions);

    return $admin;
}

test('admin marking a user as Deleted revokes their existing Sanctum tokens', function (): void {
    withoutOrdersLocaleMiddleware();

    $admin = bannedSessionAdmin(['edit users']);
    $user = User::factory()->create(['status' => UserStatusEnum::Active]);
    $user->createToken('mobile');

    expect($user->tokens()->count())->toBe(1);

    $this->actingAs($admin, 'admin')
        ->put(route('dashboard.users.update-status', $user), [
            'status' => UserStatusEnum::Deleted->value,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();

    expect($user->status)->toBe(UserStatusEnum::Deleted)
        ->and($user->tokens()->count())->toBe(0);
});

test('a user cannot complete OTP verification and receive a new token if their account was banned mid-flow', function (): void {
    $user = User::factory()->create([
        'phone' => Phone::make('512345670')->toString(),
        'status' => UserStatusEnum::Active,
    ]);

    $challenge = app(UserAuthService::class)->login('512345670');
    $otp = $user->otps()->where('purpose', OtpPurposeEnum::Login)->value('token');

    expect($challenge->success)->toBeTrue()
        ->and($otp)->not->toBeNull();

    $user->update([
        'status' => UserStatusEnum::Blocked,
        'blocked_at' => now(),
        'blocked_until' => null,
    ]);

    $result = app(UserAuthService::class)->verifyOtpSession($challenge->verificationId, $otp);

    expect($result->success)->toBeFalse()
        ->and($result->message)->toBe(__('auth.banned'))
        ->and($result->accessToken)->toBe('')
        ->and($user->fresh()->tokens()->count())->toBe(0);
});

test('an authenticated user-api request is rejected if the user status becomes inactive after token issuance', function (): void {
    $user = User::factory()->create([
        'status' => UserStatusEnum::Active,
        'phone' => Phone::make('512345671')->toString(),
    ]);

    Sanctum::actingAs($user, ['*'], 'user-api');

    $this->getJson('/api/v1/user/auth/me')
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $user->update([
        'status' => UserStatusEnum::Blocked,
        'blocked_at' => now(),
        'blocked_until' => now()->addDays(3),
    ]);

    $this->getJson('/api/v1/user/auth/me')
        ->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', __('auth.blocked'));
});
