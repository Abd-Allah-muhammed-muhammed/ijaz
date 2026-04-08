# Codebase Structure Guide

> Agent-friendly documentation for navigating this Laravel 12 + React 19 + Inertia.js application.

## Quick Reference

```
nagaz/
├── app/                    # Backend PHP application logic
├── bootstrap/              # Application bootstrapping
├── config/                 # Configuration files
├── database/               # Migrations, factories, seeders
├── lang/                   # Translation files (ar, en, hi, ur)
├── lib/                    # Custom libraries (Payment, SMS, WhatsApp)
├── public/                 # Web root (index.php, assets)
├── resources/              # Frontend assets (React, CSS, views)
├── routes/                 # Route definitions
├── storage/                # Runtime files, logs, cache
├── tests/                  # Pest test suite
└── docs/                   # Project documentation
```

---

## `/app` - Backend Application Logic

The core PHP application following Laravel conventions with DDD-style organization.

### Controllers (`app/Http/Controllers/`)

| Directory | Purpose | When to Use |
|-----------|---------|-------------|
| `Api/V1/` | Versioned API endpoints | Building mobile/external API features |
| `Dashboard/` | Admin panel controllers (~28 controllers) | Admin CRUD operations |
| `Frontend/` | Public-facing pages | User-facing features |
| `Auth/` | Authentication logic | Login, register, password reset |
| `Payments/` | Payment processing | PayTabs, wallet transactions |
| `Provider/` | Service provider logic | Provider-specific features |
| `Settings/` | App settings management | Configuration changes |

### Models (`app/Models/`)

~60+ Eloquent models organized by domain:

| Category | Models | Notes |
|----------|--------|-------|
| **Users** | `User`, `Admin`, `Provider` | Core user types |
| **Business** | `Order`, `Category`, `Skill`, `JobOffer` | Main business entities |
| **Finance** | `Payment`, `Wallet`, `WalletTransaction` | Payment handling |
| **Communication** | `Conversation`, `ConversationMessage` | Chat system |
| **Support** | `TicketSupport`, `Review` | Customer support |
| **Translatable** | `CarBrand`, `CarType`, `PropertyType` | Multi-language models |

### Services (`app/Services/`)

Complex business logic extracted from controllers:

| Service | Purpose |
|---------|---------|
| `Chat/` | Conversation management |
| `Firebase/` | Push notifications |
| `Sms/` | SMS notifications |
| `Translations/` | Translation handling |
| `Normalize/` | Data normalization |

### Traits (`app/Traits/`)

Reusable model behaviors:

| Trait | Purpose | Used By |
|-------|---------|---------|
| `HasWallet` | Wallet functionality | User, Provider |
| `HasOTPs` | OTP generation/validation | User, Provider |
| `HasPayments` | Payment handling | Order |
| `Blockable` | User blocking | User |
| `HasReviews` / `Reviewable` | Review system | Provider, Order |
| `HasBroadcastChanel` | Real-time broadcasting | User, Provider |

### Enums (`app/Enums/`)

Type-safe enumerations:

| Directory | Contains |
|-----------|----------|
| `Order/` | `OrderStatus`, `OrderType` |
| `Payment/` | `PaymentMethod`, `PaymentStatus` |
| `Users/` | User-related statuses |
| `Providers/` | Provider statuses |
| `Chat/` | Chat statuses |
| `GuaranteeRequest/` | Guarantee request states |

### Other Important Directories

| Directory | Purpose |
|-----------|---------|
| `Actions/` | Single-purpose action classes (DDD pattern) |
| `Http/Requests/` | Form Request validation classes |
| `Http/Resources/` | API Resource transformers |
| `Http/Middleware/` | Request/response middleware |
| `Events/` | Event classes for broadcasting |
| `Jobs/` | Queued background jobs |
| `Observers/` | Model event observers |
| `Notifications/` | Notification classes |
| `Console/Commands/` | Custom Artisan commands |

---

## `/resources` - Frontend Assets

React 19 + Inertia.js v2 + Tailwind CSS v4 frontend.

### JavaScript (`resources/js/`)

```
js/
├── pages/                  # Inertia page components
│   ├── Dashboard/          # Admin dashboard pages
│   ├── Frontend/           # Public pages
│   ├── Provider/           # Provider pages
│   └── Errors/             # Error pages (403, 404, 500)
├── components/             # Reusable React components
├── layouts/                # Layout wrappers
├── hooks/                  # Custom React hooks
├── helpers/                # Utility functions
├── lib/                    # Libraries and utilities
├── store/                  # State management
├── types/                  # TypeScript definitions
├── actions/                # Wayfinder-generated API actions
├── routes/                 # Wayfinder-generated routes
├── Enums/                  # Frontend enums (mirror backend)
├── lang/                   # Client-side translations
├── app.tsx                 # Application entry point
├── ssr.tsx                 # Server-side rendering entry
└── echo.js                 # Laravel Echo configuration
```

### Key Frontend Patterns

1. **Page Components**: Located in `pages/` - match backend routes
2. **Wayfinder Actions**: Import from `@/actions/...` for type-safe API calls
3. **Shared Components**: Reusable UI in `components/`
4. **Type Safety**: TypeScript types in `types/`

---

## `/routes` - Route Definitions

| File | Purpose | Auth Required |
|------|---------|---------------|
| `web.php` | Web routes entry | Session |
| `dashboard.php` | Admin routes | Admin auth |
| `api.php` | API entry point | Sanctum |
| `Api/` | Versioned API routes | Sanctum |
| `provider.php` | Provider routes | Provider auth |
| `channels.php` | Broadcasting auth | Varies |
| `console.php` | CLI commands | N/A |

---

## `/database` - Database Layer

```
database/
├── migrations/             # Schema files (~50+ migrations)
├── factories/              # Model factories for testing
└── seeders/                # Database seeders
```

### Migration Categories

| Category | Examples |
|----------|----------|
| Core | `users`, `admins`, `providers` |
| Business | `orders`, `payments`, `categories`, `skills` |
| Communication | `conversations`, `conversation_messages` |
| Translations | `*_translations` tables |
| Permissions | `roles`, `permissions` (Spatie) |

---

## `/config` - Configuration

| File | Purpose |
|------|---------|
| `app.php` | App name, timezone, locale |
| `auth.php` | Guards: `web`, `admin`, `provider`, `sanctum` |
| `database.php` | MySQL connection |
| `broadcasting.php` | Laravel Reverb config |
| `sanctum.php` | API token auth |
| `translatable.php` | Multi-language models |
| `laravellocalization.php` | Supported locales: AR, EN, HI, UR |
| `sms.php` | SMS provider config |
| `firebase.php` | Push notification config |

---

## `/lib` - Custom Libraries

External service integrations:

| Directory | Purpose |
|-----------|---------|
| `Payment/` | PayTabs integration |
| `SMS/` | SMS gateway clients |
| `WhatsApp/` | WhatsApp API client |

---

## `/tests` - Test Suite

Pest PHP testing framework:

```
tests/
├── Feature/                # Integration tests
│   ├── Api/                # API endpoint tests
│   ├── Auth/               # Authentication tests
│   └── Settings/           # Settings tests
├── Unit/                   # Unit tests
├── Pest.php                # Pest configuration
└── TestCase.php            # Base test class
```

### Running Tests

```bash
# All tests
php artisan test --compact

# Specific file
php artisan test --compact tests/Feature/ExampleTest.php

# Filter by name
php artisan test --compact --filter=testName
```

---

## `/lang` - Translations

Multi-language support with 4 locales:

| File | Language |
|------|----------|
| `ar.json` | Arabic |
| `en.json` | English |
| `hi.json` | Hindi |
| `ur.json` | Urdu |

Each locale also has a directory for nested translation files.

---

## `/bootstrap` - Application Bootstrap

| File | Purpose |
|------|---------|
| `app.php` | Middleware, exceptions, routing config |
| `providers.php` | Service provider registration |
| `cache/` | Cached config and routes |

---

## Key Architecture Patterns

### Authentication Guards

| Guard | Model | Use Case |
|-------|-------|----------|
| `web` | User | Frontend users |
| `admin` | Admin | Dashboard access |
| `provider` | Provider | Service providers |
| `sanctum` | User/Provider | API authentication |

### Multi-Language Models

Models using `Spatie\Translatable`:
- Use `*Translation` suffix for translation tables
- Access via `$model->getTranslation('name', 'ar')`

### Real-Time Features

- **Backend**: Laravel Reverb for WebSockets
- **Frontend**: Laravel Echo in `echo.js`
- **Events**: Broadcast events in `app/Events/`

### Payment Flow

```
Controller → PaymentService → PayTabs API
         ↓
    WalletTransaction → Wallet balance update
```

### File Uploads

Using Spatie Media Library:
- Models implement `HasMedia` interface
- Use `InteractsWithMedia` trait
- Files stored in `storage/app/public/`

---

## Agent Quick Commands

```bash
# Check available Artisan commands
php artisan list

# Run Pint formatter
vendor/bin/pint --dirty --format agent

# Generate Wayfinder routes
php artisan wayfinder:generate

# Run specific test
php artisan test --compact --filter=testName

# Check logs
php artisan log:tail
```

---

## File Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Controllers | PascalCase + Controller | `OrderController.php` |
| Models | PascalCase singular | `Order.php` |
| Migrations | snake_case with timestamp | `2024_01_01_create_orders_table.php` |
| Form Requests | PascalCase + Request | `StoreOrderRequest.php` |
| Resources | PascalCase + Resource | `OrderResource.php` |
| React Pages | PascalCase | `OrderIndex.tsx` |
| React Components | PascalCase | `OrderCard.tsx` |

---

## Common Workflows

### Adding a New Feature

1. Create migration: `php artisan make:migration create_x_table`
2. Create model: `php artisan make:model X -f` (with factory)
3. Create controller: `php artisan make:controller XController`
4. Create form requests: `php artisan make:request StoreXRequest`
5. Create resource: `php artisan make:resource XResource`
6. Add routes to appropriate file
7. Create Inertia page in `resources/js/pages/`
8. Run `php artisan wayfinder:generate`
9. Write tests
10. Run `vendor/bin/pint --dirty`

### Adding API Endpoint

1. Add route to `routes/Api/v1/`
2. Create/update controller in `app/Http/Controllers/Api/V1/`
3. Create form request for validation
4. Create API resource for response
5. Write feature test
6. Run Pint

### Adding Translation

1. Add key to all JSON files in `/lang/`
2. For model translations, use translatable migration
3. Frontend: import from `@/lang/`
