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

## Phase 3 — Enums ✅
- [x] `GuarantorTypeEnum` — individual / company
      Use: Collectable, HasOperations, Stringable
- [x] `GuarantorStatusEnum` — new, approved, rejected, in_progress,
      overdue, ended, cancelled, refunded
      Include: isAllowed(old, new, actor) transition matrix
      Actors: requester / counterparty / admin
- [x] `InstallmentStatusEnum` — pending, paid, released, overdue, refunded
      Use: Collectable, HasOperations, Stringable
- [x] `AuthorizationTypeEnum` — power_of_attorney / agency
      Use: Collectable, HasOperations, Stringable
- [x] Translation files: `lang/{en,ar,hi,ur}/guarantor.php`
- [x] Unit tests: `Modules/Guarantor/tests/Unit/EnumTest.php`

### Completed: 2026-06-16
### Summary:
Four enums in Modules/Guarantor/Enums/ with Collectable, HasOperations, Stringable.
Custom toString() via guarantor.* translation keys; toArray() with value, label, color.
GuarantorStatusEnum::isAllowed() covers requester, counterparty, and admin actors.
Translations added for en, ar, hi, ur. Enum unit tests registered and passing.

## Phase 4 — Models ✅
- [x] `GuarantorRequest` model
      Traits: HasUuids, SoftDeletes, InteractsWithMedia
      Casts: status → GuarantorStatusEnum, type → GuarantorTypeEnum
      Media collections: 'files' on public disk
      Relationships: requester morphTo, counterparty morphTo,
      installments hasMany, companyDetail hasOne,
      statusHistories hasMany, conversation morphOne
      Scopes: scopeForActor, scopeActive
- [x] `GuarantorInstallment` model
      Traits: HasUuids
      Casts: status → InstallmentStatusEnum
      Relationships: guarantorRequest belongsTo
- [x] `GuarantorCompanyDetail` model
      Traits: HasUuids, InteractsWithMedia
      Media collections: 'authorized_id', 'contracts',
      'iban_certificates', 'company_documents'
      Relationships: guarantorRequest belongsTo
- [x] `GuarantorStatusHistory` model
      Traits: HasUuids
      Relationships: guarantorRequest belongsTo, actor morphTo
- [x] Add `guarantorRequests()` and `assignedGuarantorRequests()`
      morphMany to User model
- [x] Factories: GuarantorRequestFactory, GuarantorInstallmentFactory

### Completed: 2026-06-16
### Summary:
Four models in Modules/Guarantor/Models/ with relationships, scopes, and media collections.
GuarantorRequest includes total accessor for SQLite compatibility.
User model extended with guarantorRequests() and assignedGuarantorRequests().
Factories with individual/company/approved/inProgress and overdue/paid states.

## Phase 5 — DTOs (readonly classes) ✅
- [x] `GuarantorData` — title, description, amount, type,
      project_type, counterparty_phone
- [x] `CompanyDetailData` — company_name, commercial_register,
      region_id, city_id, authorized_name, authorized_id_number,
      authorization_type, requester_account_holder, requester_iban,
      counterparty_account_holder, counterparty_iban
- [x] `InstallmentData` — order, amount, due_date
- [x] `UpdateGuarantorStatusData` — status, reason, notes
- [x] Unit tests: `Modules/Guarantor/tests/Unit/DTOTest.php`

### Completed: 2026-06-16
### Summary:
Four readonly DTOs in Modules/Guarantor/DTOs/ with fromRequest() factories.
Minimal FormRequest stubs added for compile-time type hints (validation rules in Phase 7).
DTOTest covers construction, fromArray, collectionFromRequest, and enum casting.

## Phase 6 — Repositories ✅
- [x] `GuarantorRepositoryInterface`
      Methods: create, update, findById,
      listByRequester, listByCounterparty, listForActor, listAll
- [x] `GuarantorRepository` (implements interface)
- [x] `InstallmentRepositoryInterface`
      Methods: create, update, findById,
      getPendingForRequest, getNextPendingForRequest, getOverdue
- [x] `InstallmentRepository`
- [x] `StatusHistoryRepositoryInterface` + `StatusHistoryRepository`
- [x] Bind all in `GuarantorServiceProvider::register()`
- [x] Unit tests: `Modules/Guarantor/tests/Unit/RepositoryTest.php`

### Completed: 2026-06-16
### Summary:
Three repository pairs (Guarantor, Installment, StatusHistory) with interfaces.
GuarantorRepository eager-loads relations in findById and list methods.
InstallmentRepository uses lazyById for getOverdue().
All interfaces bound in GuarantorServiceProvider. RepositoryTest covers CRUD, scoping, and status logging.

## Phase 7 — FormRequests ✅
All extend `MMAE\ApiResponse\Request\ApiRequest`
- [x] `StoreIndividualGuarantorRequest`
      Fields: counterparty_phone (required, CheckAuthenticatableId),
      amount (required, numeric, min:1),
      title (required, string, max:255),
      description (required, string, max:2000),
      signature (required, file, mimes:jpg,jpeg,png,pdf, max:5120)
- [x] `StoreCompanyGuarantorRequest`
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
      withValidator: installments sum must equal total_amount
- [x] `UpdateGuarantorRequest` — partial update fields
- [x] `UpdateGuarantorStatusRequest`
      Fields: status (required, GuarantorStatusEnum),
      reason (required_if cancelled or rejected, string)
- [x] `PayInstallmentRequest` (company pay)
- [x] `StoreChatRequest` — guarantor_request_id
- [x] `SendMessageRequest` — content or files
- [x] Translation keys in lang/{en,ar,hi,ur}/guarantor.php
- [x] Unit tests: `Modules/Guarantor/tests/Unit/FormRequestTest.php`

### Completed: 2026-06-16
### Summary:
Seven FormRequests with full validation rules extending ApiRequest.
StoreCompanyGuarantorRequest validates installment sum via withValidator().
Validation and success message keys added to all four locales.
FormRequestTest covers required fields, sum mismatch, status reason rules, and chat messages.

### Phase 7 fix (2026-06-17)
- `project_type` required on StoreCompanyGuarantorRequest
- Encrypted casts on GuarantorCompanyDetail bank fields (IBAN, account holders)

## Phase 8 — Actions ✅
- [x] `GuarantorException` + bootstrap registration
- [x] `LogGuarantorStatusHistoryAction`
- [x] `CreateIndividualGuarantorAction`
- [x] `CreateCompanyGuarantorAction`
- [x] `UpdateGuarantorAction`
- [x] `DeleteGuarantorAction`
- [x] `UpdateGuarantorStatusAction` (approve/reject + auto-open chat)
- [x] `OpenGuarantorChatAction`
- [x] `DeleteGuarantorMediaAction`
- [x] `PayIndividualGuarantorAction`
- [x] `PayInstallmentAction`
- [x] `ReleaseInstallmentAction`
- [x] `EndGuarantorAction`
- [x] `CancelGuarantorAction`
- [x] Feature tests: `Modules/Guarantor/tests/Feature/GuarantorActionTest.php`

### Completed: 2026-06-17
### Summary:
Thirteen actions under Modules/Guarantor/Actions/ with DB::transaction on writes.
GuarantorException registered in bootstrap/app.php with model not-found keys.
Status history logged on every status change; notifications deferred to Phase 13.
ApproveGuarantorAction/RejectGuarantorAction consolidated into UpdateGuarantorStatusAction.

## Phase 9 — Services ✅
- [x] `GuarantorService` (orchestration only)
      Methods: createIndividual, createCompany, update, delete,
      updateStatus, deleteMedia, payIndividual, end, cancel,
      loadForShow, listForActor, listAll, resolveActorRole
- [x] `GuarantorInstallmentService`
      Methods: pay, release, getNextPending, listForRequest
- [x] `GuarantorChatService`
      Methods: open, listForActor, listMessages, send
- [x] Chat action stubs: ListGuarantorChatsAction,
      ListGuarantorChatMessagesAction, SendGuarantorChatMessageAction
- [x] `GuarantorConversationMessenger` support class

### Completed: 2026-06-17
### Summary:
Three orchestration services delegate to Actions with zero business logic.
Chat list/send actions stubbed for Phase 14 full integration.
GuarantorService includes resolveActorRole() helper for controllers/policies.

## Phase 10 — Policies ✅
- [x] `GuarantorPolicy`
      update/delete/deleteMedia: requester + status new
      updateStatus: party only
      pay: counterparty + status approved
      end: party + status in_progress/overdue
      cancel: party + status new/approved
      chat: party + status approved/in_progress/overdue
      view: party only
- [x] `InstallmentPolicy` — pay: counterparty only
- [x] `ConversationPolicy` — view/send: participant only
- [x] Register policies in `GuarantorServiceProvider::boot()`
- [x] Unit tests: `Modules/Guarantor/tests/Unit/PolicyTest.php`

### Completed: 2026-06-17
### Summary:
GuarantorPolicy, InstallmentPolicy, and ConversationPolicy return bool only.
ConversationPolicy uses participant check (registered in app booted callback so it wins over Opportunity's policy).
GuarantorPolicy/InstallmentPolicy cast morph ids to string for uuidMorph column compatibility.
PolicyTest covers requester/counterparty/stranger scenarios via Gate::forUser().

## Phase 11 — Controllers ✅
- [x] `GuarantorController`
      index, storeIndividual, storeCompany, show,
      update, destroy, deleteMedia, updateStatus, pay
- [x] `GuarantorChatController`
      index, store, show, send
- [x] `InstallmentController`
      index, pay

### Completed: 2026-06-17
### Summary:
Thin V1 controllers delegate to GuarantorService, GuarantorInstallmentService, and GuarantorChatService.
Validate → DTO → authorize → service → JsonResponse via HasApiResponse.
GuarantorConversationResource + API resources created alongside controllers (Phase 12 overlap).

## Phase 12 — Resources ✅
- [x] `GuarantorResource` — all fields, status→toArray(),
      installments whenLoaded, companyDetail whenLoaded,
      statusHistories whenLoaded, media whenLoaded
- [x] `GuarantorCollection` extends BaseCollection
- [x] `InstallmentResource` — id, order, amount, due_date,
      paid_at, released_at, status→toArray(), is_past_due
- [x] `CompanyDetailResource`
- [x] `StatusHistoryResource`
- [x] `GuarantorConversationResource` + `GuarantorConversationCollection`
- [x] `GuarantorParticipantResource`
- [x] `GuarantorDashboardResource` + `GuarantorDashboardCollection`
- [x] Unit tests: `Modules/Guarantor/tests/Unit/ResourceTest.php`

### Completed: 2026-06-17
### Summary:
API resources use whenLoaded for relations; enums expose toArray() with label and color.
Dashboard resources include admin_notes and eager relation output for Phase 17.
ResourceTest covers field shape, enum serialization, is_past_due, and encrypted IBAN access.

## Phase 13 — Notifications ✅
- [x] `GuarantorCreatedNotification` → notify counterparty
- [x] `GuarantorApprovedNotification` → notify requester
- [x] `GuarantorRejectedNotification` → notify requester
- [x] `InstallmentDueNotification` → notify counterparty (day 1)
- [x] `InstallmentOverdueNotification` → notify both (day 3)
- [x] `InstallmentReleasedNotification` → notify requester
- [x] `GuarantorEndedNotification` → notify both (end + cancel)
All follow Opportunity notification pattern:
database + broadcast + firebase (firebase for User only)
Translation keys in lang/{en,ar,hi,ur}.json
Wired into create, updateStatus, end, cancel, and release actions.

### Completed: 2026-06-17
### Summary:
Seven notifications implement ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldQueue.
toArray stores translation keys; toBroadcast/toFirebase resolve at send time.
InstallmentDue/Overdue classes ready for Phase 15 scheduled jobs.
GuarantorActionTest extended with five notification assertions.

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

### Phase 3 — Enums (2026-06-16)
- Added GuarantorTypeEnum, GuarantorStatusEnum, InstallmentStatusEnum, AuthorizationTypeEnum
- Added lang/en|ar|hi|ur/guarantor.php translation files
- Added EnumTest.php with isAllowed() coverage; registered in phpunit.xml and Pest.php

### Phase 4 — Models (2026-06-16)
- Added GuarantorRequest, GuarantorInstallment, GuarantorCompanyDetail, GuarantorStatusHistory models
- Added GuarantorRequestFactory and GuarantorInstallmentFactory
- Extended User with guarantorRequests() and assignedGuarantorRequests() morph relationships

### Phase 5 — DTOs (2026-06-16)
- Added GuarantorData, CompanyDetailData, InstallmentData, UpdateGuarantorStatusData readonly DTOs
- Added minimal FormRequest stubs for DTO type hints (full validation in Phase 7)
- Added DTOTest.php with 5 unit tests

### Phase 6 — Repositories (2026-06-16)
- Added GuarantorRepository, InstallmentRepository, StatusHistoryRepository with interfaces
- Bound all repositories in GuarantorServiceProvider
- Added RepositoryTest.php with 9 unit tests

### Phase 7 — FormRequests (2026-06-16)
- Added seven FormRequests with validation rules extending ApiRequest
- Added validation/success translation keys to en, ar, hi, ur
- Added FormRequestTest.php with 9 unit tests
- Fixed mmae/apiresponse ApiRequest trait namespace typo (Apiresponse → ApiResponse)

### Phase 8 — Actions (2026-06-17)
- Added GuarantorException and thirteen actions (create, update, delete, status, chat, payment, installment, end, cancel)
- Registered exception handler and model not-found keys in bootstrap/app.php
- Encrypted bank fields on GuarantorCompanyDetail; project_type required on company store request
- Added GuarantorActionTest.php with 15 feature tests

### Phase 9 — Services (2026-06-17)
- Added GuarantorService, GuarantorInstallmentService, GuarantorChatService
- Added chat action stubs and GuarantorConversationMessenger support class

### Phase 10 — Policies (2026-06-17)
- Added GuarantorPolicy, InstallmentPolicy, ConversationPolicy
- Registered policies in GuarantorServiceProvider
- Added PolicyTest.php with 24 unit tests

### Phase 11 — Controllers (2026-06-17)
- Added GuarantorController, InstallmentController, GuarantorChatController (V1)
- Added API resources: GuarantorResource, GuarantorCollection, InstallmentResource,
  CompanyDetailResource, StatusHistoryResource, GuarantorConversationResource
- Controllers use HasApiResponse, DTOs, and $this->authorize() — zero business logic

### Phase 12 — Resources (2026-06-17)
- Completed GuarantorResource, InstallmentResource, CompanyDetailResource, StatusHistoryResource,
  GuarantorParticipantResource; added GuarantorDashboardResource + Collection
- ResourceTest.php with 7 unit tests for serialization and encrypted fields

### Phase 13 — Notifications (2026-06-17)
- Added seven notifications in Modules/Guarantor/Notifications/
- Wired into create, updateStatus, end, cancel, and release installment actions
- Translation keys in en, ar, hi, ur JSON files; five notification tests in GuarantorActionTest
