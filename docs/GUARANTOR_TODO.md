# Guarantor Module — Todo List

> Reference module: Modules/Opportunity/
> Branch: feat/guarantor-module
> Last updated: 2026-06-16

---

## Phase 1 — Module Scaffold ✅
- [x] Create module skeleton via `php artisan module:make Guarantor`
- [x] Configure `module.json` and `composer.json`
- [x] Create `GuarantorServiceProvider` + `RouteServiceProvider`
- [x] Register module in `modules_statuses.json`
- [x] Run `composer dump-autoload`

### Completed: 2026-06-16
### Summary:
Module scaffold created under Modules/Guarantor/.
Follows Opportunity module structure exactly.
ServiceProvider + RouteServiceProvider registered.
Empty route files and directory structure in place.
composer dump-autoload ran successfully with no errors.

## Phase 2 — Database ✅
- [x] Migration: `create_guarantor_requests_table`
      Columns: id (uuid), type, requester morph, counterparty morph,
      title, description, amount, fees, total (storedAs), status,
      project_type (nullable), requester_signature (nullable),
      cancellation_reason (nullable), admin_notes (nullable),
      overdue_at, ended_at, cancelled_at, refunded_at,
      timestamps, softDeletes
- [x] Migration: `create_guarantor_installments_table`
      Columns: id (uuid), guarantor_request_id (FK), order (int),
      amount, due_date, paid_at, released_at, overdue_notified_at,
      status (enum), timestamps
- [x] Migration: `create_guarantor_company_details_table`
      Columns: id (uuid), guarantor_request_id (FK unique),
      company_name, commercial_register, region_id, city_id,
      authorized_name, authorized_id_number, authorization_type,
      requester_account_holder, requester_iban,
      counterparty_account_holder, counterparty_iban (nullable),
      timestamps
- [x] Migration: `create_guarantor_status_histories_table`
      Columns: id (uuid), guarantor_request_id (FK),
      actor morph, from_status (nullable), to_status,
      reason (nullable), notes (nullable), timestamps
- [x] Run migrations and verify tables

### Completed: 2026-06-16
### Summary:
Created 4 migrations under Modules/Guarantor/database/migrations/.
Tables: guarantor_requests, guarantor_installments, guarantor_company_details, guarantor_status_histories.
UUID PKs on all tables; uuidMorphs on requester, counterparty, and actor.
total column uses storedAs on MySQL; plain decimal default on SQLite for tests.
php artisan migrate ran successfully — all 4 tables verified.

## Phase 3 — Enums
- [ ] `GuarantorTypeEnum` — individual / company
      Use: Collectable, HasOperations, Stringable
- [ ] `GuarantorStatusEnum` — new, approved, rejected, in_progress,
      overdue, ended, cancelled, refunded
      Include: isAllowed(old, new, actor) transition matrix
      Actors: requester / counterparty / admin
- [ ] `InstallmentStatusEnum` — pending, paid, released, overdue, refunded
      Use: Collectable, HasOperations, Stringable
- [ ] `AuthorizationTypeEnum` — power_of_attorney / agency
      Use: Collectable, HasOperations, Stringable

## Phase 4 — Models
- [ ] `GuarantorRequest` model
      Traits: HasUuids, SoftDeletes, InteractsWithMedia
      Casts: status → GuarantorStatusEnum, type → GuarantorTypeEnum
      Media collections: 'files' on public disk
      Relationships: requester morphTo, counterparty morphTo,
      installments hasMany, companyDetail hasOne,
      statusHistories hasMany, conversation morphOne
      Scopes: scopeForActor, scopeActive
- [ ] `GuarantorInstallment` model
      Traits: HasUuids
      Casts: status → InstallmentStatusEnum
      Relationships: guarantorRequest belongsTo
- [ ] `GuarantorCompanyDetail` model
      Traits: HasUuids, InteractsWithMedia
      Media collections: 'authorized_id', 'contracts',
      'iban_certificates', 'company_documents'
      Relationships: guarantorRequest belongsTo
- [ ] `GuarantorStatusHistory` model
      Traits: HasUuids
      Relationships: guarantorRequest belongsTo, actor morphTo
- [ ] Add `guarantorRequests()` and `assignedGuarantorRequests()`
      morphMany to User model

## Phase 5 — DTOs (readonly classes)
- [ ] `GuarantorData` — title, description, amount, type,
      project_type, counterparty_phone
- [ ] `CompanyDetailData` — company_name, commercial_register,
      region_id, city_id, authorized_name, authorized_id_number,
      authorization_type, requester_account_holder, requester_iban,
      counterparty_account_holder, counterparty_iban
- [ ] `InstallmentData` — order, amount, due_date
- [ ] `UpdateGuarantorStatusData` — status, reason, notes

## Phase 6 — Repositories
- [ ] `GuarantorRepositoryInterface`
      Methods: create, update, findById,
      listByRequester, listByCounterparty, listAll
- [ ] `GuarantorRepository` (implements interface)
- [ ] `InstallmentRepositoryInterface`
      Methods: create, updateStatus, getOverdue, getPendingForRequest
- [ ] `InstallmentRepository`
- [ ] Bind all in `GuarantorServiceProvider::register()`

## Phase 7 — FormRequests
All extend `MMAE\ApiResponse\Request\ApiRequest`
- [ ] `StoreIndividualGuarantorRequest`
      Fields: counterparty_phone (required, CheckAuthenticatableId),
      amount (required, numeric, min:1),
      title (required, string, max:255),
      description (required, string, max:2000),
      signature (required, file, mimes:jpg,jpeg,png,pdf, max:5120)
- [ ] `StoreCompanyGuarantorRequest`
      Fields: counterparty_phone, project_type, total_amount,
      installments (array, min:1), installments.*.amount,
      installments.*.due_date (date, after:today),
      company_name, commercial_register, region_id, city_id,
      authorized_name, authorized_id_number, authorization_type,
      requester_account_holder, requester_iban,
      counterparty_account_holder, counterparty_iban (nullable),
      signature (required, file), authorized_id (file),
      contracts (array of files), iban_certificate (file, nullable),
      company_documents (array of files)
- [ ] `UpdateGuarantorStatusRequest`
      Fields: status (required, GuarantorStatusEnum),
      reason (required_if cancelled or rejected, string)
- [ ] `StoreInstallmentPaymentRequest` (company pay)

## Phase 8 — Actions
- [ ] `CreateIndividualGuarantorAction`
      handle(GuarantorData, Model $requester, Request): GuarantorRequest
      DB::transaction: create record, upload signature to media,
      notify counterparty
- [ ] `CreateCompanyGuarantorAction`
      handle(GuarantorData, CompanyDetailData, InstallmentData[],
      Model $requester, Request): GuarantorRequest
      DB::transaction: create record, create company details,
      create installments, upload all media, notify counterparty
- [ ] `ApproveGuarantorAction`
      handle(GuarantorRequest, Model $actor): GuarantorRequest
      Validate: actor is counterparty, status is new
      Update status → approved, open chat, log history,
      notify requester
- [ ] `RejectGuarantorAction`
      handle(GuarantorRequest, string $reason, Model $actor): void
      Validate: actor is counterparty, status is new
      Update status → rejected, log history, notify requester
- [ ] `PayIndividualGuarantorAction`
      handle(GuarantorRequest, Model $actor): array
      Validate: actor is counterparty, status is approved
      Create Payment, initiate PayTabs, return gateway response
- [ ] `PayInstallmentAction`
      handle(GuarantorRequest, GuarantorInstallment, Model $actor): array
      Validate: actor is counterparty, installment status is pending,
      previous installment is paid
      Create Payment for installment, initiate PayTabs
- [ ] `ReleaseInstallmentAction`
      handle(GuarantorInstallment): void
      DB::transaction: update installment status → released,
      release pending_credit to balance for requester wallet,
      log history, notify requester
- [ ] `AutoReleaseOverdueInstallmentAction`
      handle(GuarantorInstallment): void
      Same as ReleaseInstallmentAction but triggered by scheduler
- [ ] `EndGuarantorAction`
      handle(GuarantorRequest, Model $actor): void
      Validate: actor is requester or counterparty, status is in_progress
      Update status → ended, release last pending amounts,
      log history, notify both parties
- [ ] `CancelGuarantorAction`
      handle(GuarantorRequest, string $reason, Model $actor): void
      Validate: allowed statuses (new, approved),
      Update status → cancelled, reverse any holds if paid,
      log history, notify both parties
- [ ] `AdminCancelGuarantorAction`
      handle(GuarantorRequest, string $reason, string $notes, Admin $admin): void
      Admin only, any status, full cancel + refund initiation
- [ ] `OpenGuarantorChatAction`
      handle(GuarantorRequest): Conversation
      firstOrCreate conversation, validate status is approved+
- [ ] `LogGuarantorStatusHistoryAction`
      handle(GuarantorRequest, from, to, actor, reason, notes): void
      Called from every status-changing action

## Phase 9 — Services
- [ ] `GuarantorService` (orchestration only)
      Methods: createIndividual, createCompany,
      approve, reject, payIndividual, payInstallment,
      end, cancel, loadForShow
- [ ] `GuarantorInstallmentService`
      Methods: releaseInstallment, checkOverdue

## Phase 10 — Policies
- [ ] `GuarantorPolicy`
      update: requester + status new
      delete: requester + status new
      deleteMedia: requester + status new
      approve: counterparty + status new
      reject: counterparty + status new
      pay: counterparty + status approved
      end: requester or counterparty + status in_progress
      cancel: requester or counterparty + status new/approved
      chat: requester or counterparty + status approved+
- [ ] Register all policies in `GuarantorServiceProvider::boot()`

## Phase 11 — Controllers (thin — zero logic)
- [ ] `GuarantorController`
      index, store (individual + company), show,
      update, destroy, deleteMedia, updateStatus,
      pay, end, cancel
- [ ] `GuarantorChatController`
      index, store, show, send
- [ ] `InstallmentController`
      index, pay

## Phase 12 — Resources
- [ ] `GuarantorResource` — all fields, status→toArray(),
      installments whenLoaded, companyDetail whenLoaded,
      statusHistories whenLoaded, media whenLoaded
- [ ] `GuarantorCollection` extends BaseCollection
- [ ] `InstallmentResource` — id, order, amount, due_date,
      paid_at, released_at, status→toArray()
- [ ] `InstallmentCollection` extends BaseCollection
- [ ] `CompanyDetailResource`
- [ ] `StatusHistoryResource`

## Phase 13 — Notifications
- [ ] `GuarantorCreatedNotification` → notify counterparty
- [ ] `GuarantorApprovedNotification` → notify requester
- [ ] `GuarantorRejectedNotification` → notify requester
- [ ] `InstallmentDueNotification` → notify counterparty (day 1)
- [ ] `InstallmentOverdueNotification` → notify both (day 3)
- [ ] `InstallmentReleasedNotification` → notify requester
- [ ] `GuarantorEndedNotification` → notify both
- [ ] `GuarantorCancelledNotification` → notify both
All follow Opportunity notification pattern:
database + broadcast + firebase (firebase for User only)
Translation keys in lang/{en,ar,hi,ur}.json

## Phase 14 — Payment Pipeline
- [ ] Move + fix `ProcessGuarantorPayment` action
      (was ProcessGuaranteeRequest — fix types + add installment support)
- [ ] Move + fix `AddRequesterWalletTransaction`
      (was AddUserTransactionForGuaranteeRequest)
- [ ] Move + fix `AddCounterpartyWalletTransaction`
      (was AddProviderTransactionForGuaranteeRequest + fix fee deduction)
- [ ] Implement `NotifyRequesterAfterPayment`
      (was empty stub)
- [ ] Implement `NotifyCounterpartyAfterPayment`
      (was empty stub)
- [ ] Add PayTabs callback route for Guarantor
      `POST /api/payments/paytabs/guarantor/{payment}/callback`
- [ ] Implement `SettleGuarantorAction` on payment success
      release pending → balance on completion

## Phase 15 — Scheduled Jobs
- [ ] `CheckOverdueInstallmentsCommand`
      `php artisan guarantor:check-overdue` runs daily
      Finds installments past due_date, dispatches jobs
- [ ] `NotifyOverdueInstallmentJob` (day 1 + day 3 notifications)
- [ ] `AutoReleaseInstallmentJob` (day 14 auto-release)
- [ ] Register command + schedule in `GuarantorServiceProvider`
- [ ] Add to `opportunities` queue (or dedicated `guarantor` queue)

## Phase 16 — Routes
- [ ] `Modules/Guarantor/Routes/V1/api.php`
      Individual + company store, show, update, destroy,
      updateStatus, pay, end, cancel, deleteMedia
- [ ] `Modules/Guarantor/Routes/V1/chat.php`
      chats/guarantor → index, store, show, send
- [ ] `Modules/Guarantor/Routes/V1/installments.php`
      guarantor/{id}/installments → index, pay
- [ ] `Modules/Guarantor/Routes/dashboard.php`
      Admin: index, show, updateStatus, cancel, releaseInstallment

## Phase 17 — Dashboard (Admin)
- [ ] `GuarantorDashboardController`
      index (with filters: status, type, date),
      show (full details + installments + history),
      updateStatus (with reason — required),
      cancel (with reason + notes),
      releaseInstallment (manual release)
- [ ] Dashboard resources
- [ ] React pages:
      Dashboard/Guarantor/Index.tsx
      Dashboard/Guarantor/Show.tsx (tabs: overview, installments, history, chat)
- [ ] Sidebar menu item with permission guard
- [ ] Permissions seeder:
      'show guarantors', 'manage guarantors'

## Phase 18 — Translations
- [ ] `lang/{en,ar,hi,ur}/guarantor.php`
      status labels, type labels, error messages, success messages
- [ ] `lang/{en,ar,hi,ur}.json`
      notification translation keys

## Phase 19 — Exceptions
- [ ] `GuarantorException` (single class like OpportunityException)
      constructor(translationKey, httpStatusCode)
      render() → JSON response

## Phase 20 — Tests
- [ ] Individual CRUD tests
- [ ] Company CRUD tests (with installments)
- [ ] Status transition tests (all allowed + blocked)
- [ ] Payment flow tests
- [ ] Installment release tests
- [ ] Overdue + auto-release tests
- [ ] Policy tests (requester / counterparty / admin)
- [ ] Notification tests
- [ ] Chat tests

## Phase 21 — Scramble Docs
- [ ] Add PHPDoc to all controllers
- [ ] Add @response examples
- [ ] Add @unauthenticated where applicable
- [ ] Verify Scramble generates valid OpenAPI

## Phase 22 — Cleanup
- [ ] Run Pint on entire module
- [ ] Update PROJECT_CONTEXT.md
- [ ] Update docs/API_INVENTORY.md
- [ ] Update docs/MODELS_REFERENCE.md
- [ ] Update GUARANTOR_MOBILE_CHANGES.md with all endpoints
- [ ] Final commit on feat/guarantor-module
- [ ] Open PR to develop

---

## Completed Steps Log
<!-- Each completed step gets moved here with summary -->

### Phase 1 — Module Scaffold (2026-06-16)
- Created `Modules/Guarantor/` with Opportunity-aligned structure (Providers/, Routes/, Actions/, etc.)
- Configured `module.json`, module `composer.json`, and root `composer.json` autoload paths
- Added `GuarantorServiceProvider` and `RouteServiceProvider` extending `BaseModuleRouteServiceProvider`
- Registered module in `modules_statuses.json`
- Created empty route stubs: `Routes/V1/api.php`, `Routes/V1/chat.php`, `Routes/dashboard.php`
- Ran `composer dump-autoload` — no errors; module shows as Enabled in `php artisan module:list`

### Phase 2 — Database (2026-06-16)
- Added migrations `2026_06_16_000001` through `000004` for requests, installments, company details, status histories
- SQLite-safe `total` column (storedAs skipped on sqlite driver)
- `city_id` FK references `cities` table (matches Opportunity / existing schema)
- All migrations applied successfully via `php artisan migrate`
