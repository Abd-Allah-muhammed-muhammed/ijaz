# Modules

This project uses `nwidart/laravel-modules` for modular organization. Do not create modules outside the `Modules/` folder.

## Base Structure

```
Modules/{Name}/
├── Http/
│   ├── Controllers/V1/
│   ├── Requests/
│   └── Resources/
├── Contracts/
├── Models/
├── Services/
├── Repositories/
├── Routes/
│   └── V1/
│       └── api.php
├── Providers/
│   ├── {Name}ServiceProvider.php
│   └── RouteServiceProvider.php
└── Database/
    ├── Migrations/
    └── Seeders/
```

## Routing Conventions

- V1 routes live in `Routes/V1/api.php` and are registered under `/api/v1` with the `api` middleware.
- V2 routes (optional) live in `Routes/V2/api.php` and are registered under `/api/v2` with the `api` middleware.
- Route names should be prefixed with `api.v1.{module}.` or `api.v2.{module}.`.

## Providers

- `Providers/RouteServiceProvider.php` should extend `App\Providers\BaseModuleRouteServiceProvider` and set the `$moduleName` property.
- `{Name}ServiceProvider.php` should register the module RouteServiceProvider.

```php
// Each module's RouteServiceProvider should extend:
use App\Providers\BaseModuleRouteServiceProvider;
```
