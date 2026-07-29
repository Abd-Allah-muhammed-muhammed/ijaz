<?php

use App\Enums\Users\UserStatusEnum;
use App\Http\Controllers\Dashboard\UserController;
use App\Models\Admin;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Geo\Models\Nationality;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
});

function createUserManagementAdmin(array $permissions): Admin
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
        'name' => 'Users Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $admin->givePermissionTo($permissions);

    return $admin;
}

function createUserManagementNationality(string $name = 'Saudi'): Nationality
{
    return Nationality::query()->create([
        'code' => 'SA',
        'is_active' => true,
        'translations' => [
            'en' => ['name' => $name],
            'ar' => ['name' => $name],
            'ur' => ['name' => $name],
        ],
    ]);
}

it('lists users with the prams inertia prop', function (): void {
    $admin = createUserManagementAdmin(['show users']);
    User::factory()->create(['f_name' => 'Listed', 'l_name' => 'User']);

    $this->actingAs($admin, 'admin')
        ->get(action([UserController::class, 'index'], ['search' => 'Listed']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Users/Index')
            ->has('prams')
            ->has('rows.data', 1)
        );
});

test('users index page receives accurate status counts', function (): void {
    $admin = createUserManagementAdmin(['show users']);

    User::factory()->create(['status' => UserStatusEnum::Active]);
    User::factory()->create(['status' => UserStatusEnum::Active]);
    User::factory()->create(['status' => UserStatusEnum::Blocked]);
    User::factory()->create(['status' => UserStatusEnum::Deleted]);

    // Force pagination below the true total so page-scoped client filters would undercount.
    $this->actingAs($admin, 'admin')
        ->get(action([UserController::class, 'index'], ['per_page' => 1]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Users/Index')
            ->has('rows.data', 1)
            ->has('stats')
            ->where('stats.total', 4)
            ->where('stats.active', 2)
            ->where('stats.blocked', 1)
            ->missing('stats.deleted')
        );
});

it('serves the nationality dropdown through Geo on the create form', function (): void {
    $admin = createUserManagementAdmin(['create users']);
    createUserManagementNationality();

    $this->actingAs($admin, 'admin')
        ->get(action([UserController::class, 'create']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Users/Create')
            ->has('nationalities', 1)
        );
});

it('renders a user with its wallet transactions', function (): void {
    $admin = createUserManagementAdmin(['show users']);
    $user = User::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get(action([UserController::class, 'show'], $user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Users/Show')
            ->has('user')
            ->has('transactions')
            ->has('prams')
        );
});

it('stores a user with a normalized phone', function (): void {
    Storage::fake('public');

    $admin = createUserManagementAdmin(['create users']);
    $nationality = createUserManagementNationality();

    $this->actingAs($admin, 'admin')
        ->post(action([UserController::class, 'store']), [
            'f_name' => 'New',
            'l_name' => 'Client',
            'email' => 'client@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '512345678',
            'nationality_id' => $nationality->id,
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $created = User::query()->where('email', 'client@example.com')->firstOrFail();

    expect($created->phone)->toBe(Phone::make('512345678')->toString())
        ->and($created->image)->not->toBeNull();
});

it('updates a user without requiring a new password', function (): void {
    Storage::fake('public');

    $admin = createUserManagementAdmin(['edit users']);
    $nationality = createUserManagementNationality();
    $user = User::factory()->create([
        'email' => 'target@example.com',
        'nationality_id' => $nationality->id,
        'password' => Hash::make('original-password'),
    ]);
    $originalPassword = $user->password;

    $this->actingAs($admin, 'admin')
        ->put(action([UserController::class, 'update'], $user), [
            'f_name' => 'Renamed',
            'l_name' => 'Client',
            'email' => 'target@example.com',
            'phone' => '512345679',
            'nationality_id' => $nationality->id,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();

    expect($user->f_name)->toBe('Renamed')
        ->and($user->password)->toBe($originalPassword)
        ->and($user->phone)->toBe(Phone::make('512345679')->toString());
});

it('deletes a user', function (): void {
    $admin = createUserManagementAdmin(['delete users']);
    $user = User::factory()->create();

    $this->actingAs($admin, 'admin')
        ->delete(action([UserController::class, 'destroy'], $user))
        ->assertRedirect(route('dashboard.users.index'));

    expect(User::query()->whereKey($user->getKey())->exists())->toBeFalse();
});

it('blocks a user and revokes its tokens when the status becomes blocked', function (): void {
    $admin = createUserManagementAdmin(['edit users']);
    $user = User::factory()->create();
    $user->createToken('mobile');

    $this->actingAs($admin, 'admin')
        ->put(route('dashboard.users.update-status', $user), [
            'status' => UserStatusEnum::Blocked->value,
            'block_days' => 3,
            'block_reason' => 'abuse',
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();

    expect($user->status)->toBe(UserStatusEnum::Blocked)
        ->and($user->blocked_at)->not->toBeNull()
        ->and($user->blocked_until)->not->toBeNull()
        ->and($user->tokens()->count())->toBe(0)
        ->and($user->blockHistories()->where('reason', 'abuse')->exists())->toBeTrue();
});
