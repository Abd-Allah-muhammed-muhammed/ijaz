<?php

use App\Http\Controllers\Dashboard\AuthController;
use App\Models\Admin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
    Storage::fake('public');
});

function createProfileAdmin(array $attributes = []): Admin
{
    return Admin::query()->create([
        'name' => 'Profile Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
        'address' => 'Riyadh',
        'job' => 'Admin',
        'image' => 'admins/existing.png',
        ...$attributes,
    ]);
}

test('admin can view their profile page', function (): void {
    $admin = createProfileAdmin();

    $this->actingAs($admin, 'admin')
        ->get(action([AuthController::class, 'profile']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Profile/Index')
            ->where('admin.id', $admin->id)
            ->where('admin.email', $admin->email)
            ->where('admin.name', $admin->name)
        );
});

test('admin can update their name/email without providing password', function (): void {
    $admin = createProfileAdmin([
        'password' => Hash::make('password'),
    ]);
    $originalHash = $admin->password;

    $this->actingAs($admin, 'admin')
        ->post(action([AuthController::class, 'updateProfile']), [
            'name' => 'Updated Name',
            'email' => 'updated-admin@example.com',
            'phone' => $admin->phone,
            'address' => $admin->address,
            'job' => $admin->job,
        ])
        ->assertRedirect(route('dashboard.profile'));

    $admin->refresh();

    expect($admin->name)->toBe('Updated Name')
        ->and($admin->email)->toBe('updated-admin@example.com')
        ->and($admin->password)->toBe($originalHash);
});

test('admin can update their password when providing password + confirmation', function (): void {
    $admin = createProfileAdmin([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(action([AuthController::class, 'updateProfile']), [
            'name' => $admin->name,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'address' => $admin->address,
            'job' => $admin->job,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect(route('dashboard.profile'));

    expect(Hash::check('new-password', $admin->fresh()->password))->toBeTrue();
});

test('admin can update their profile image', function (): void {
    $admin = createProfileAdmin([
        'image' => 'admins/old.png',
    ]);

    $file = UploadedFile::fake()->image('avatar.jpg');

    $this->actingAs($admin, 'admin')
        ->post(action([AuthController::class, 'updateProfile']), [
            'name' => $admin->name,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'address' => $admin->address,
            'job' => $admin->job,
            'image' => $file,
        ])
        ->assertRedirect(route('dashboard.profile'));

    $admin->refresh();

    expect($admin->image)->not->toBe('admins/old.png')
        ->and($admin->image)->not->toBeNull()
        ->and(Storage::disk('public')->exists($admin->image))->toBeTrue();
});
