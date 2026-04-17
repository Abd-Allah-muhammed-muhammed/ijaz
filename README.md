# Ijaz

Ijaz is a multi-actor marketplace platform built with Laravel and Inertia React.
It supports users, providers, and admins with features for orders, offers, chat,
wallets, payments, guarantee requests, advisements, and support tickets.

## Tech Stack

- PHP 8.2+
- Laravel 13
- Inertia.js 3 + React 19
- Tailwind CSS 4
- Sanctum authentication
- Reverb broadcasting

## Quick Start

1. Install PHP dependencies:
   - `composer install`
2. Install Node dependencies:
   - `npm install`
3. Configure environment:
   - `cp .env.example .env`
   - Update database and service credentials in `.env`
4. Generate app key:
   - `php artisan key:generate`
5. Run migrations and seeders:
   - `php artisan migrate --seed`
6. Start frontend build/dev process:
   - `npm run dev`

Note: In this environment, Laravel Herd serves the app; no manual `php artisan serve` is required.

## Documentation Map

Start with:

- [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md)

Detailed references:

- [docs/API_INVENTORY.md](docs/API_INVENTORY.md)
- [docs/MODELS_REFERENCE.md](docs/MODELS_REFERENCE.md)
- [docs/ENUMS_REFERENCE.md](docs/ENUMS_REFERENCE.md)
- [docs/REFACTOR_NOTES.md](docs/REFACTOR_NOTES.md)

## Common Commands

- Run tests: `php artisan test --compact`
- Format PHP: `vendor/bin/pint --dirty --format agent`
- List routes: `php artisan route:list --except-vendor`
- Generate Wayfinder routes/actions: `php artisan wayfinder:generate`

## Notes

- API responses should use the `HasApiResponse` helpers and Resources.
- Controllers should stay thin and delegate business logic to Services/Actions.
- Status values should use enums from `app/Enums`.
