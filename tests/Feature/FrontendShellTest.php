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

test('website home sets data-app web and shares app.shell', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('data-app="web"', false)
        ->assertInertia(fn ($page) => $page
            ->where('app.shell', 'web')
            ->where('app.locale', app()->getLocale())
        );
});

test('dashboard login sets data-app admin and shares app.shell', function () {
    $this->get(route('dashboard.login.form'))
        ->assertSuccessful()
        ->assertSee('data-app="admin"', false)
        ->assertInertia(fn ($page) => $page
            ->where('app.shell', 'admin')
        );
});

test('provider login sets data-app provider and shares app.shell', function () {
    $this->get(route('provider.login'))
        ->assertSuccessful()
        ->assertSee('data-app="provider"', false)
        ->assertInertia(fn ($page) => $page
            ->where('app.shell', 'provider')
        );
});
