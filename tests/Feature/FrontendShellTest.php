<?php

use App\Enums\FrontendShell;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

test('frontend shell resolves from route name prefixes', function (string $routeName, FrontendShell $expected) {
    $route = new RoutingRoute(['GET'], '/', fn () => null);
    $route->name($routeName);

    $request = Request::create('/');
    $request->setRouteResolver(fn () => $route);

    expect(FrontendShell::fromRequest($request))->toBe($expected);
})->with([
    ['dashboard.home', FrontendShell::Admin],
    ['dashboard.login.form', FrontendShell::Admin],
    ['dashboard.users.index', FrontendShell::Admin],
    ['provider.home', FrontendShell::Provider],
    ['provider.login', FrontendShell::Provider],
    ['provider.orders.index', FrontendShell::Provider],
    ['marketer.home', FrontendShell::Marketer],
    ['home', FrontendShell::Web],
    ['about-us', FrontendShell::Web],
    ['auth.register', FrontendShell::Web],
]);

test('website home shares app.shell', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('app.shell', 'web')
            ->where('app.locale', app()->getLocale())
        );
});

test('dashboard login shares app.shell admin', function () {
    $this->get(route('dashboard.login.form'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('app.shell', 'admin')
        );
});

test('provider login shares app.shell provider', function () {
    $this->get(route('provider.login'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('app.shell', 'provider')
        );
});
