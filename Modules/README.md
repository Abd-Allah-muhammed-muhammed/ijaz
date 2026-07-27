# Modules

This project uses `nwidart/laravel-modules` for modular organization. Do not create modules outside the `Modules/` folder.

Layering, FormRequest placement, DTOs, and explicit-import rules are defined in
[`.cursor/rules/layered-architecture.mdc`](../.cursor/rules/layered-architecture.mdc) —
follow that document rather than inventing per-module variants.

## Base Structure

```
Modules/{Name}/
├── Actions/
├── Contracts/
│   └── Repositories/
├── DTOs/
├── Enums/
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/          # mobile / Sanctum API
│   │   ├── Dashboard/       # admin Inertia
│   │   └── Provider/        # provider portal (when needed)
│   ├── Requests/
│   └── Resources/
├── Models/
├── Providers/
│   ├── {Name}ServiceProvider.php
│   └── RouteServiceProvider.php
├── Repositories/
├── Routes/
│   ├── V1/                  # api.php / domain route files under /api/v1
│   ├── dashboard.php        # admin routes (when needed)
│   └── provider.php         # provider routes (when needed)
├── Services/
├── database/                # lowercase — factories, migrations, seeders
│   ├── factories/
│   ├── migrations/
│   └── seeders/
└── tests/
```

Not every module needs every folder — omit empty placeholders. Controllers live under
`Http/Controllers/{Api/V1,Dashboard,Provider}` (not a flat `Controllers/V1/`).

## Routing Conventions

- API V1 routes live under `Routes/V1/` and are registered under `/api/v1` with the `api` middleware.
- Dashboard / provider routes live as `Routes/dashboard.php` / `Routes/provider.php` when the module owns those surfaces.
- Route names should be prefixed with `api.v1.{module}.` (or the dashboard/provider equivalent used by sibling modules).
- Prefer owning routes inside the module (`RouteServiceProvider`) rather than registering them from `routes/`.

## Providers

- `Providers/RouteServiceProvider.php` should extend `App\Providers\BaseModuleRouteServiceProvider` and set the `$moduleName` property.
- Prefer overriding `mapApiRoutes()` (and dashboard/provider mappers) over a full `map()` override — see existing Cms/Jobs providers.
- `{Name}ServiceProvider.php` registers bindings, events, and the module `RouteServiceProvider`.

```php
use App\Providers\BaseModuleRouteServiceProvider;
```

## Database path casing

Use lowercase `database/` (with `factories/`, `migrations/`, `seeders/`). Namespace remains
`Modules\{Name}\Database\Factories\` — map it explicitly in the module `composer.json`
when factories live under the lowercase path (see Geo / Payment / Wallet).
