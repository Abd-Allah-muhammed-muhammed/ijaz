<?php

use App\Http\Requests\Dashboard\Auth\DashboardLoginRequest;
use App\Models\Admin;
use App\Services\Auth\AdminAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

function createAuthAdmin(array $attributes = []): Admin
{
    return Admin::query()->create([
        'name' => 'Auth Admin',
        'phone' => fake()->unique()->numerify('9665########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
        ...$attributes,
    ]);
}

test('login regenerates session and returns dashboard redirect result', function () {
    $admin = createAuthAdmin();

    $request = DashboardLoginRequest::create('/dashboard/login', 'POST', [
        'email' => $admin->email,
        'password' => 'password',
    ]);
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setLaravelSession($session = app('session.store'));
    $session->start();

    $previousSessionId = $session->getId();

    $result = app(AdminAuthService::class)->login($request);

    expect($result->redirectRouteName)->toBe('dashboard.home')
        ->and(Auth::guard('admin')->id())->toBe($admin->id)
        ->and($session->getId())->not->toBe($previousSessionId);
});

test('logout invalidates session and regenerates csrf token', function () {
    $admin = createAuthAdmin();
    $this->actingAs($admin, 'admin');

    $request = Request::create('/dashboard/logout', 'POST');
    $request->setLaravelSession($session = app('session.store'));
    $session->start();
    $previousToken = $session->token();

    app(AdminAuthService::class)->logout($request);

    expect(Auth::guard('admin')->check())->toBeFalse()
        ->and($session->token())->not->toBe($previousToken);
});
