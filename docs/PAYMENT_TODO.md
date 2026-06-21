# Payment Module — Todo List

> Branch: feature/payment-module
> Last updated: 2026-06-21
> Goal: Move ALL payment-related code into Modules/Payment/ — completely self-contained.
>       If someone deletes Modules/Payment/, no payment code should remain anywhere else.
>
> Pattern: Controller → Service → Action + Events/Listeners per module
> Golden Rule: API response shapes do NOT change — only backend moves.

---

## Dependency Map

```
app/Enums/OperationStatusEnum     ← stays in app/ (used by Wallet too)
app/Models/User                   → uses Modules\Payment\Traits\HasPayments
app/Models/Provider               → uses Modules\Payment\Traits\HasPayments
app/Models/Order                  → uses payment via OrderPaymentListener
Modules\Wallet\                   → listens to PaymentCompleted for TopUp
Modules\Guarantor\                → listens to PaymentCompleted for Guarantor

Modules/Payment/
  ├── Models/Payment               ← moves from app/Models/
  ├── Traits/HasPayments           ← moves from app/Traits/
  ├── Enums/PaymentDriver|Status|Method ← moves from app/Enums/Payment/
  ├── Gateways/PayTabsGateway      ← replaces lib/Payment/Gates/PayTabsGate
  ├── Gateways/TestingGateway      ← replaces lib/Payment/Gates/TestingGate
  ├── Services/PaymentService      ← replaces lib/Payment/Facade + Manager
  └── Events/PaymentCompleted      ← consumed by Order, Wallet, Guarantor modules
```

---

## Phase 1 — Module Scaffold
- [x] Create module: `php artisan module:make Payment`
- [x] Configure `module.json` and `composer.json`
- [x] Create `PaymentServiceProvider` + `RouteServiceProvider`
- [x] Register in `modules_statuses.json`
- [x] Create full directory structure with .gitkeep:
```
Modules/Payment/
├── Actions/
│   └── Order/
├── config/
├── Contracts/
├── Database/
│   └── Migrations/
├── DTOs/
├── Enums/
├── Events/
├── Gateways/
├── Handlers/
├── Http/
│   ├── Controllers/
│   └── Resources/
├── Models/
├── Registry/
├── Resources/
│   └── views/
│       └── payment/
├── Routes/
├── Services/
└── Traits/
```
- [x] Remove scaffold artifacts
- [x] `composer dump-autoload`
- [x] Verify: `php artisan module:list` shows Payment enabled

### Completed: 2026-06-21
### Summary: Created `Modules/Payment/` skeleton mirroring Wallet/Guarantor patterns — `PaymentServiceProvider` (config merge, migrations, views), `RouteServiceProvider` (api/web route stubs with `web` middleware for bindings), empty directory tree with `.gitkeep`, minimal `config/payment.php`, route stubs for Phase 11. Registered as enabled in `modules_statuses.json`. All 386 regression tests pass.

---

## Phase 2 — Config

- [x] Create `Modules/Payment/config/payment.php`:
```php
return [
    'default' => env('PAYMENT_DRIVER', 'paytabs'),

    'drivers' => [
        'paytabs' => [
            'mode' => env('PAYTABS_MODE', 'test'),
            'test' => [
                'profile_id' => env('PAYTABS_TEST_PROFILE_ID'),
                'server_key'  => env('PAYTABS_TEST_SERVER_KEY'),
                'client_key'  => env('PAYTABS_TEST_CLIENT_KEY'),
                'currency'    => env('PAYTABS_TEST_CURRENCY', 'SAR'),
                'region'      => env('PAYTABS_TEST_REGION', 'SAU'),
                'endpoint'    => 'https://secure-egypt.paytabs.com',
            ],
            'live' => [
                'profile_id' => env('PAYTABS_LIVE_PROFILE_ID'),
                'server_key'  => env('PAYTABS_LIVE_SERVER_KEY'),
                'client_key'  => env('PAYTABS_LIVE_CLIENT_KEY'),
                'currency'    => env('PAYTABS_LIVE_CURRENCY', 'SAR'),
                'region'      => env('PAYTABS_LIVE_REGION', 'SAU'),
                'endpoint'    => 'https://secure.paytabs.com',
            ],
        ],

        'rajhi' => [
            'mode' => env('RAJHI_MODE', 'test'),
            'test' => [
                'tranportal_id'       => env('RAJHI_TEST_TRANPORTAL_ID'),
                'tranportal_password' => env('RAJHI_TEST_TRANPORTAL_PASSWORD'),
                'resource_key'        => env('RAJHI_TEST_RESOURCE_KEY'),
                'currency'            => env('RAJHI_TEST_CURRENCY', '682'),
                'endpoint'            => 'https://securepayments.alrajhibank.com.sa/pg/payment/hosted.htm',
            ],
            'live' => [
                'tranportal_id'       => env('RAJHI_LIVE_TRANPORTAL_ID'),
                'tranportal_password' => env('RAJHI_LIVE_TRANPORTAL_PASSWORD'),
                'resource_key'        => env('RAJHI_LIVE_RESOURCE_KEY'),
                'currency'            => env('RAJHI_LIVE_CURRENCY', '682'),
                'endpoint'            => 'https://digitalpayments.alrajhibank.com.sa/pg/payment/hosted.htm',
            ],
        ],
    ],
];
```
- [x] Register config in `PaymentServiceProvider::register()`:
```php
$this->mergeConfigFrom(module_path('Payment', 'config/payment.php'), 'payment');
```
- [x] Remove payment section from `config/app.php`
- [x] Update `.env.example` with new payment keys
- [x] Verify: `config('payment.default')` works

### Completed: 2026-06-21
### Summary: Centralized payment config in `Modules/Payment/config/payment.php` with PayTabs and Rajhi drivers (test/live mode structure). Removed legacy `payment` block from `config/app.php`. Updated `.env.example` with structured env keys. Verified via tinker; all 386 regression tests pass.

---

## Phase 3 — Move Migration

- [x] Move `database/migrations/2025_08_21_193400_create_payments_table.php`
      → `Modules/Payment/Database/Migrations/`
- [x] Register migrations path in `PaymentServiceProvider::boot()`:
```php
$this->loadMigrationsFrom(module_path('Payment', 'Database/Migrations'));
```
- [x] Verify: `php artisan migrate:status` shows payment migration as Ran
- [x] Run tests — 386 must pass

### Completed: 2026-06-21
### Summary: Moved `2025_08_21_193400_create_payments_table.php` to `Modules/Payment/Database/Migrations/` unchanged. Migration path already registered in `PaymentServiceProvider`. `migrate:status` shows Ran; all 386 regression tests pass.

---

## Phase 4 — Move Model + Trait + Enums

### Task 1 — Move Payment model
FROM: `app/Models/Payment.php`
TO:   `Modules/Payment/Models/Payment.php`
- Update namespace to `Modules\Payment\Models`
- Update all imports across codebase
- Leave backward-compat alias in old location

### Task 2 — Move HasPayments trait
FROM: `app/Traits/HasPayments.php`
TO:   `Modules/Payment/Traits/HasPayments.php`
- Update namespace
- Update `User.php` and `Provider.php` imports
- Leave backward-compat alias

### Task 3 — Move Enums
FROM: `app/Enums/Payment/PaymentDriverEnum.php`
TO:   `Modules/Payment/Enums/PaymentDriverEnum.php`

FROM: `app/Enums/Payment/PaymentStatusEnum.php`
TO:   `Modules/Payment/Enums/PaymentStatusEnum.php`

FROM: `app/Enums/Payment/PaymentMethodEnum.php`
TO:   `Modules/Payment/Enums/PaymentMethodEnum.php`

- Update namespaces
- Search and update ALL imports across codebase:
```bash
grep -rn "App\\\\Enums\\\\Payment\\" app/ Modules/ --include="*.php" | grep -v vendor
```
- Leave backward-compat aliases

Run tests after — 386 must pass.

### Completed: —
### Summary: —

---

## Phase 5 — Contracts + DTOs

### Task 1 — PaymentGatewayInterface
File: `Modules/Payment/Contracts/PaymentGatewayInterface.php`
```php
interface PaymentGatewayInterface
{
    public function initiate(Payment $payment): PaymentInitResult;
    public function verify(Payment $payment, array $payload): PaymentVerifyResult;
    public function getConfig(): array;  // returns active mode config
}
```

### Task 2 — PaymentHandlerInterface
File: `Modules/Payment/Contracts/PaymentHandlerInterface.php`
```php
interface PaymentHandlerInterface
{
    // Called synchronously inside DB transaction after payment verified
    public function onSuccess(Payment $payment): void;
    public function onFailure(Payment $payment): void;
    public function productTypes(): array;  // e.g. [OrderOffer::class]
}
```

### Task 3 — PaymentInitResult DTO
File: `Modules/Payment/DTOs/PaymentInitResult.php`
```php
readonly class PaymentInitResult
{
    public function __construct(
        public string  $status,        // 'success' | 'failed'
        public string  $driver,
        public string  $url,
        public bool    $payable,
        public ?string $transactionId = null,
        public ?string $message       = null,
        public array   $data          = [],
    ) {}

    public function toArray(): array { ... }
}
```

### Task 4 — PaymentVerifyResult DTO
File: `Modules/Payment/DTOs/PaymentVerifyResult.php`
```php
readonly class PaymentVerifyResult
{
    public function __construct(
        public PaymentStatusEnum $status,
        public ?string           $transactionId = null,
        public array             $rawResponse   = [],
        public ?string           $message       = null,
    ) {}
}
```

### Completed: —
### Summary: —

---

## Phase 6 — Gateways

### Task 1 — PayTabsGateway
File: `Modules/Payment/Gateways/PayTabsGateway.php`

Replaces: `lib/Payment/Gates/PayTabsGate.php`
- Implements `PaymentGatewayInterface`
- Uses `config('payment.drivers.paytabs.' . config('payment.drivers.paytabs.mode'))`
- `initiate()` → builds PayTabs hosted page, returns `PaymentInitResult`
- `verify()` → queries PayTabs with `tranRef`, returns `PaymentVerifyResult`
- NO domain model imports (no Guarantor, no Order)
- Callback URLs resolved via route names only

### Task 2 — TestingGateway
File: `Modules/Payment/Gateways/TestingGateway.php`

Replaces: `lib/Payment/Gates/TestingGate.php`
- Implements `PaymentGatewayInterface`
- `initiate()` → returns mock URL
- `verify()` → reads status from request, returns `PaymentVerifyResult`

### Completed: —
### Summary: —

---

## Phase 7 — PaymentHandlerRegistry + Events

### Task 1 — PaymentHandlerRegistry
File: `Modules/Payment/Registry/PaymentHandlerRegistry.php`

```php
class PaymentHandlerRegistry
{
    private array $handlers = [];

    public function register(string $productType, PaymentHandlerInterface $handler): void
    {
        $this->handlers[$productType] = $handler;
    }

    public function getHandler(string $productType): PaymentHandlerInterface
    {
        if (!isset($this->handlers[$productType])) {
            throw new \RuntimeException("No payment handler for: {$productType}");
        }
        return $this->handlers[$productType];
    }

    public function hasHandler(string $productType): bool
    {
        return isset($this->handlers[$productType]);
    }
}
```

Register as singleton in `PaymentServiceProvider::register()`.

### Task 2 — PaymentCompleted Event
File: `Modules/Payment/Events/PaymentCompleted.php`
```php
class PaymentCompleted
{
    public function __construct(
        public readonly Payment $payment,
    ) {}
}
```

### Task 3 — PaymentFailed Event
File: `Modules/Payment/Events/PaymentFailed.php`
```php
class PaymentFailed
{
    public function __construct(
        public readonly Payment $payment,
    ) {}
}
```

### Completed: —
### Summary: —

---

## Phase 8 — PaymentService

File: `Modules/Payment/Services/PaymentService.php`

Single entry point for initiating payments:

```php
class PaymentService
{
    public function __construct(
        private readonly PaymentHandlerRegistry $registry,
    ) {}

    public function initiate(
        Model  $owner,
        Model  $product,
        float  $amount,
        string $driver = null,
    ): PaymentInitResult {
        $driver  = $driver ?? config('payment.default');
        $gateway = $this->resolveGateway($driver);

        // Create Payment record
        $payment = $owner->payments()->create([
            'product_type' => $product::class,
            'product_id'   => $product->getKey(),
            'amount'       => $amount,
            'status'       => PaymentStatusEnum::Pending,
            'driver'       => $driver,
        ]);

        return $gateway->initiate($payment);
    }

    public function resolveGateway(string $driver): PaymentGatewayInterface { ... }

    public function getDefaultDriver(): string
    {
        return config('payment.default');
    }
}
```

Bind in `PaymentServiceProvider::register()`.

### Completed: —
### Summary: —

---

## Phase 9 — HandleCallbackAction + PaymentCallbackController

### Task 1 — HandleCallbackAction
File: `Modules/Payment/Actions/HandleCallbackAction.php`

```php
class HandleCallbackAction
{
    public function handle(Payment $payment, array $payload): void
    {
        // 1. Idempotency guard
        if ($payment->status !== PaymentStatusEnum::Pending) {
            return;
        }

        // 2. Get gateway and verify
        $gateway = app(PaymentService::class)->resolveGateway($payment->driver);
        $result  = $gateway->verify($payment, $payload);

        // 3. Update payment inside transaction
        DB::transaction(function () use ($payment, $result) {
            $payment->update([
                'status'         => $result->status,
                'transaction_id' => $result->transactionId,
                'response'       => $result->rawResponse,
            ]);

            // 4. Call handler synchronously inside transaction
            if ($result->status === PaymentStatusEnum::Accepted) {
                if (app(PaymentHandlerRegistry::class)->hasHandler($payment->product_type)) {
                    app(PaymentHandlerRegistry::class)
                        ->getHandler($payment->product_type)
                        ->onSuccess($payment);
                }
            } else {
                if (app(PaymentHandlerRegistry::class)->hasHandler($payment->product_type)) {
                    app(PaymentHandlerRegistry::class)
                        ->getHandler($payment->product_type)
                        ->onFailure($payment);
                }
            }
        });

        // 5. Fire event AFTER transaction commits
        DB::afterCommit(function () use ($payment, $result) {
            if ($result->status === PaymentStatusEnum::Accepted) {
                event(new PaymentCompleted($payment));
            } else {
                event(new PaymentFailed($payment));
            }
        });
    }
}
```

### Task 2 — PaymentCallbackController
File: `Modules/Payment/Http/Controllers/PaymentCallbackController.php`

Replaces: `app/Http/Controllers/Payments/PayTabsController.php`

```php
class PaymentCallbackController extends Controller
{
    public function __construct(
        private readonly HandleCallbackAction $handleCallbackAction,
    ) {}

    // GET|POST /payments/{driver}/{payment}/redirect
    public function redirect(Payment $payment, Request $request): RedirectResponse
    {
        $this->handleCallbackAction->handle($payment, $request->all());

        return match ($payment->status) {
            PaymentStatusEnum::Accepted => redirect()->route('payment.success', $payment),
            default                     => redirect()->route('payment.failed', $payment),
        };
    }

    // GET|POST /payments/{driver}/{payment}/callback
    // Webhook — same handler, different HTTP response
    public function callback(Payment $payment, Request $request): Response
    {
        $this->handleCallbackAction->handle($payment, $request->all());
        return response('OK', 200);
    }
}
```

### Completed: —
### Summary: —

---

## Phase 10 — Payment Handlers (in Payment module)

These handlers run synchronously inside the DB transaction:

### Task 1 — OrderPaymentHandler
File: `Modules/Payment/Handlers/OrderPaymentHandler.php`

```php
class OrderPaymentHandler implements PaymentHandlerInterface
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function onSuccess(Payment $payment): void
    {
        $offer = $payment->product;

        // Update offer + order status
        $offer->update(['status' => OfferStatusEnum::Paid]);
        $offer->order->update([
            'status' => OrderStatusEnum::InProgress,
            'price'  => $payment->amount,
        ]);

        // Wallet operations
        $this->walletService->addPendingDebit(
            owner:     $payment->user,
            amount:    $payment->amount,
            operation: $offer,
        );
        $this->walletService->adjustPending(
            owner:       $offer->provider,
            creditDelta: $offer->order->price,
            debitDelta:  $offer->order->provider_fees,
            operation:   $offer,
        );
    }

    public function onFailure(Payment $payment): void {}

    public function productTypes(): array
    {
        return [OrderOffer::class];
    }
}
```

### Task 2 — TopUpPaymentHandler
File: `Modules/Payment/Handlers/TopUpPaymentHandler.php`

```php
class TopUpPaymentHandler implements PaymentHandlerInterface
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function onSuccess(Payment $payment): void
    {
        $topUp = $payment->product;

        // Update TopUpRequest status
        $topUp->update([
            'status'         => OperationStatusEnum::Approved,
            'payment_status' => PaymentStatusEnum::Accepted,
            'transaction_id' => $payment->transaction_id,
            'payment_driver' => $payment->driver,
        ]);

        // Credit wallet
        $this->walletService->credit(
            owner:     $payment->user,
            amount:    $payment->amount,
            operation: $topUp,
            description: 'Online top-up approved',
        );
    }

    public function onFailure(Payment $payment): void {}

    public function productTypes(): array
    {
        return [TopUpRequest::class];
    }
}
```

### Task 3 — GuarantorPaymentHandler
File: `Modules/Payment/Handlers/GuarantorPaymentHandler.php`

```php
class GuarantorPaymentHandler implements PaymentHandlerInterface
{
    public function onSuccess(Payment $payment): void
    {
        // Delegate to Guarantor domain action
        // ProcessGuarantorPayment stays in Guarantor module
        // This handler just fires — Guarantor module listens via PaymentCompleted event
        // So this handler can be empty OR call Guarantor action directly

        // Decision: empty — let GuarantorServiceProvider listener handle it
    }

    public function onFailure(Payment $payment): void {}

    public function productTypes(): array
    {
        return [
            \Modules\Guarantor\Models\GuarantorRequest::class,
            \Modules\Guarantor\Models\GuarantorInstallment::class,
        ];
    }
}
```

### Task 4 — Register handlers in PaymentServiceProvider::boot()

```php
public function boot(): void
{
    parent::boot();

    $registry = $this->app->make(PaymentHandlerRegistry::class);

    $registry->register(OrderOffer::class, app(OrderPaymentHandler::class));
    $registry->register(TopUpRequest::class, app(TopUpPaymentHandler::class));
    $registry->register(GuarantorRequest::class, app(GuarantorPaymentHandler::class));
    $registry->register(GuarantorInstallment::class, app(GuarantorPaymentHandler::class));
}
```

### Completed: —
### Summary: —

---

## Phase 11 — Routes

File: `Modules/Payment/Routes/api.php`:
```php
// Callback routes (no auth)
Route::prefix('payments')->group(function () {
    Route::match(['get', 'post'], '{driver}/{payment}/redirect',
        [PaymentCallbackController::class, 'redirect'])->name('payment.redirect');
    Route::match(['get', 'post'], '{driver}/{payment}/callback',
        [PaymentCallbackController::class, 'callback'])->name('payment.callback');
});
```

File: `Modules/Payment/Routes/web.php`:
```php
Route::get('/payments/{payment}/success', fn() => view('payment::success'))->name('payment.success');
Route::get('/payments/{payment}/failed',  fn() => view('payment::failed'))->name('payment.failed');
```

- Move `resources/views/payment/` → `Modules/Payment/Resources/views/`
- Register view namespace `payment::` in PaymentServiceProvider
- Remove old payment routes from `routes/api.php` and `routes/web.php`
- Verify: `php artisan route:list | grep payment`

### Completed: —
### Summary: —

---

## Phase 12 — Update Callers to use PaymentService

Replace ALL `Payment::create() + PaymentFacade::pay()` with `PaymentService::initiate()`:

- [ ] `app/Http/Controllers/Api/V1/User/OrderController::pay()`
      ```php
      // BEFORE
      $payment = $user->payments()->create([...]);
      $paymentObject = Payment::pay($payment);

      // AFTER
      $result = $this->paymentService->initiate(
          owner:   $user,
          product: $offer,
          amount:  $order->user_total,
      );
      return $this->successResponse($result->toArray());
      ```

- [ ] `Modules/Wallet/Http/Controllers/V1/WalletController::addBalance()`
      Replace inline payment creation with `PaymentService::initiate()`

- [ ] `Modules/Wallet/Http/Controllers/Provider/TopUpController::store()`
      Same — replace with `PaymentService::initiate(driver: $request->driver)`

- [ ] `Modules/Guarantor/Actions/Payment/PayIndividualGuarantorAction`
      Replace with `PaymentService::initiate()`

- [ ] `Modules/Guarantor/Actions/Installment/PayInstallmentAction`
      Replace with `PaymentService::initiate()`

- [ ] Remove `app/Actions/Payment/PayTabs/UpdatePaymentStatus.php` (replaced by gateway verify)
- [ ] Remove `app/Actions/Payment/Test/UpdatePaymentStatus.php`
- [ ] Remove `app/Actions/Payment/Order/ProcessOrder.php` (moved to OrderPaymentHandler)
- [ ] Remove `app/Actions/Payment/Order/AddProviderTransaction.php` (moved to handler)
- [ ] Remove `app/Actions/Payment/Order/AddUserTransaction.php` (moved to handler)
- [ ] Remove `app/Actions/Payment/TopUp/ProcessToUpRequest.php` (moved to handler)
- [ ] Remove `app/Actions/Payment/AddModelTransaction.php` (moved to handler)
- [ ] Remove `app/Actions/Payment/NotifyModelForOp.php` (no-op)
- [ ] Remove `app/Actions/Payment/Order/NotifyProviderForOrder.php` (no-op)
- [ ] Remove `app/Actions/Payment/Order/NotifyUserForOrder.php` (no-op)

Run tests after — 386 must pass.

### Completed: —
### Summary: —

---

## Phase 13 — Events/Listeners in consuming modules

This phase wires up the event listeners in every module that consumes PaymentCompleted.
Critical: without this phase, post-payment domain logic breaks.

### Task 1 — Guarantor module listener

File: `Modules/Guarantor/Listeners/HandleGuarantorPaymentCompleted.php`

```php
class HandleGuarantorPaymentCompleted
{
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        if (!in_array($payment->product_type, [
            GuarantorRequest::class,
            GuarantorInstallment::class,
        ])) {
            return; // Not a guarantor payment
        }

        // Run existing ProcessGuarantorPayment logic
        app(ProcessGuarantorPayment::class)->handle($payment);
    }
}
```

Register in `GuarantorServiceProvider::boot()`:
```php
Event::listen(PaymentCompleted::class, HandleGuarantorPaymentCompleted::class);
```

### Task 2 — Notification listeners (async/queued)

File: `Modules/Guarantor/Listeners/NotifyGuarantorPaymentCompleted.php`
```php
// implements ShouldQueue
// Sends push/email notifications after payment
// Replaces NotifyGuarantorPayment no-op action
```

Register in `GuarantorServiceProvider::boot()`:
```php
Event::listen(PaymentCompleted::class, NotifyGuarantorPaymentCompleted::class);
```

### Task 3 — Order notification listener (async)

File: `app/Listeners/Payment/NotifyOrderPaymentCompleted.php`
```php
// implements ShouldQueue
// Sends notification to user + provider after order payment
// Replaces NotifyProviderForOrder + NotifyUserForOrder no-ops
```

Register in `AppServiceProvider::boot()`:
```php
Event::listen(PaymentCompleted::class, NotifyOrderPaymentCompleted::class);
```

### Task 4 — PaymentFailed listeners

Same pattern for failed payments:
- `Modules/Guarantor/Listeners/HandleGuarantorPaymentFailed.php`
- `app/Listeners/Payment/NotifyOrderPaymentFailed.php`

### Completed: —
### Summary: —

---

## Phase 14 — Cleanup

- [ ] Delete `lib/Payment/` directory completely
- [ ] Delete `app/Actions/Payment/` directory completely
- [ ] Delete `app/Http/Controllers/Payments/PayTabsController.php`
- [ ] Delete `app/Http/Resources/PayTapResponseResource.php`
- [ ] Delete `app/Models/Payment.php` (alias)
- [ ] Delete `app/Traits/HasPayments.php` (alias)
- [ ] Delete `app/Enums/Payment/` directory
- [ ] Delete `resources/views/payment/` (moved to module)
- [ ] Remove `PaypageServiceProvider` from `bootstrap/providers.php`
      (paytabs package no longer needed — we call PayTabs API directly)
- [ ] Remove payment section from `config/app.php`
- [ ] Run: `php artisan route:list | grep payment` — same routes
- [ ] `npm run build` — no errors
- [ ] Run ALL tests — 386 must pass

### Completed: —
### Summary: —

---

## Phase 15 — Tests

- [ ] Tests for `PaymentService::initiate()`
- [ ] Tests for `PayTabsGateway` (mock HTTP calls)
- [ ] Tests for `TestingGateway`
- [ ] Tests for `HandleCallbackAction` (idempotency + happy path + failure)
- [ ] Tests for `OrderPaymentHandler::onSuccess()`
- [ ] Tests for `TopUpPaymentHandler::onSuccess()`
- [ ] Tests for `GuarantorPaymentHandler` + listener
- [ ] Tests for `PaymentCallbackController` routes
- [ ] Tests for `PaymentCompleted` event dispatch
- [ ] Tests for `HandleGuarantorPaymentCompleted` listener
- [ ] Tests for `NotifyOrderPaymentCompleted` listener (queued)
- [ ] Regression: ALL 386 existing tests pass

### Completed: —
### Summary: —

---

## Bug Fixes (fixed during build)

| # | Bug | Fixed in Phase |
|---|-----|----------------|
| 1 | Double callback processing (no idempotency) | Phase 9 |
| 2 | `PaymentStatusEnum::Canceled->value` mixed in match arm | Phase 9 |
| 3 | `refund()` returns null (violates return type) | Phase 6 |
| 4 | `PayTabsGate` imports Guarantor models | Phase 6 |
| 5 | Config split across `config/app.php` + hardcoded values | Phase 2 |
| 6 | `OrderController` wrong failure check (`!$paymentObject->getStatus()`) | Phase 12 |
| 7 | All Notify* actions are no-ops | Phase 13 |

---

## Notes
- PaymentService is the ONLY entry point for initiating payments
- HandleCallbackAction is the ONLY entry point for processing callbacks
- Handlers run SYNCHRONOUSLY inside DB transaction (wallet + domain state)
- Events fire AFTER transaction commits via DB::afterCommit()
- Listeners for notifications run ASYNC (implements ShouldQueue)
- Guarantor domain logic stays in Guarantor module via listener
- Route URLs do NOT change — same paths, same names
- API response shapes do NOT change
- `OperationStatusEnum` stays in `app/Enums/` — shared with Wallet
