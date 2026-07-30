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
        ->assertSee('id="design-system-bs-bridge"', false)
        ->assertSee('--bs-primary: var(--primary)', false)
        ->assertInertia(fn ($page) => $page
            ->where('app.shell', 'admin')
        );
});

test('bootstrap variable bridge in app.css uses html data-app specificity', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain("html[data-app='admin']")
        ->toContain("html[data-app='admin'][data-bs-theme='light']")
        ->toContain('--bs-primary: var(--primary)')
        ->toContain('--bs-info: var(--info)')
        ->toContain('--bs-primary-rgb: 0, 75, 151');
});

test('admin shell tokens and layout entry exist for Tailwind Admin Shell', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    $layout = file_get_contents(resource_path('js/apps/admin/layouts/AdminLayout.tsx'));
    $sidebar = file_get_contents(resource_path('js/shared/components/Sidebar/Sidebar.tsx'));

    expect($css)->toContain('--admin-shell-sidebar:');
    expect($layout)->toContain('AdminShellProvider');
    expect($sidebar)->not->toContain('data-kt-app-sidebar');
    expect($sidebar)->not->toContain('app-sidebar');
});

test('provider login sets data-app provider and shares app.shell', function () {
    $this->get(route('provider.login'))
        ->assertSuccessful()
        ->assertSee('data-app="provider"', false)
        ->assertInertia(fn ($page) => $page
            ->where('app.shell', 'provider')
        );
});
