# Enums Reference

Regenerated from the live codebase (`app/Enums` + `Modules/*/Enums`). Utility traits live under `app/Enums/Utilities/` and are documented separately below.

**Last verified: 2026-07-26, post-full-module-extraction**

Model cast cross-references come from the regenerated `docs/MODELS_REFERENCE.md` plus a live scan of model `casts()` methods. Short-name collisions (e.g. two `OfferStatusEnum` classes) are disambiguated by module.

## Scope

| Group | Enums |
|---|---:|
| App Core | 6 |
| Chat | 2 |
| Classifieds | 7 |
| Guarantor | 4 |
| Opportunity | 2 |
| Orders | 2 |
| Payment | 3 |
| Sms | 1 |
| Support | 1 |
| Wallet | 1 |
| **Total** | **29** |

---

# App Core

## `App\Enums\CategoryFeesTypeEnum`

**File:** `app/Enums/CategoryFeesTypeEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `INHERITED` | `inherited` |
| `FIXED` | `fixed` |
| `PERCENTAGE` | `percentage` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `Category.fees_type`

---

## `App\Enums\Jobs\JobTypeEnum`

**File:** `app/Enums/Jobs/JobTypeEnum.php`  
**Backing type:** `int`

| Case | Value |
|---|---|
| `Governmental` | `1` |
| `Private` | `2` |

### Utility traits
- None

### Used by models (casts)
- `JobOffer.type`

---

## `App\Enums\OperationStatusEnum`

**File:** `app/Enums/OperationStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Pending` | `pending` |
| `Rejected` | `rejected` |
| `Approved` | `approved` |

### Utility traits
- `App\Enums\Utilities\HasOperations`

### Used by models (casts)
- `TopUpRequest.status`
- `WithdrawRequest.status`

---

## `App\Enums\Providers\ProviderStatusEnum`

**File:** `app/Enums/Providers/ProviderStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Pending` | `pending` |
| `Approved` | `approved` |
| `Suspended` | `suspended` |
| `Rejected` | `rejected` |
| `Blocked` | `blocked` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `Provider.status`

---

## `App\Enums\ProviderTypeFilesEnum`

**File:** `app/Enums/ProviderTypeFilesEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `ID_IMAGE` | `id_image` |
| `COMMERCIAL_RECORD` | `commercial_record` |
| `FREELANCER_CERTIFICATION` | `freelancer_certification` |
| `IBAN_CERTIFICATION` | `iban_certification` |
| `LICENSE_TO_PRACTICE_LAW` | `license_to_practice_law` |

### Utility traits
- `App\Enums\Utilities\Collectable`

### Used by models (casts)
- None detected in model casts (may still be used in validation, events, DTOs, or JS enum generation)

---

## `App\Enums\Users\UserStatusEnum`

**File:** `app/Enums/Users/UserStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Active` | `active` |
| `Blocked` | `blocked` |
| `Deleted` | `deleted` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `User.status`

---

# Chat

## `Modules\Chat\Enums\ChatEventEnum`

**File:** `Modules/Chat/Enums/ChatEventEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `New_Message` | `new-message` |
| `Chat_Updated` | `chat-updated` |

### Utility traits
- `App\Enums\Utilities\HasOperations`

### Used by models (casts)
- None detected in model casts (may still be used in validation, events, DTOs, or JS enum generation)

---

## `Modules\Chat\Enums\ChatTypeEnum`

**File:** `Modules/Chat/Enums/ChatTypeEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Member` | `member` |
| `Order` | `order` |
| `TicketSupport` | `ticket_support` |
| `Opportunity` | `opportunity` |
| `Guarantor` | `guarantor` |

### Utility traits
- None

### Used by models (casts)
- None detected in model casts (may still be used in validation, events, DTOs, or JS enum generation)

---

# Classifieds

## `Modules\Classifieds\Enums\AdvisementStatusEnum`

**File:** `Modules/Classifieds/Enums/AdvisementStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `PUBLISHED` | `published` |
| `PENDING` | `pending` |
| `REJECTED` | `rejected` |
| `CLOSED` | `closed` |

### Utility traits
- None

### Used by models (casts)
- `CarAdvisement.status`
- `ElectronicAdvisement.status`
- `InstituteAdvisement.status`
- `PropertyAdvisement.status`

---

## `Modules\Classifieds\Enums\ElectronicConditionEnum`

**File:** `Modules/Classifieds/Enums/ElectronicConditionEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `NEW` | `new` |
| `USED` | `used` |
| `LESS_THAN_YEAR` | `less_than_year` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\HasTranslations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `ElectronicAdvisement.condition`

---

## `Modules\Classifieds\Enums\InstituteTypeEnum`

**File:** `Modules/Classifieds/Enums/InstituteTypeEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `INSTITUTE` | `institute` |
| `UNIVERSITY` | `university` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\HasTranslations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `InstituteAdvisement.type`

---

## `Modules\Classifieds\Enums\OperationEnum`

**File:** `Modules/Classifieds/Enums/OperationEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `SALE` | `sale` |
| `RENT` | `rent` |
| `BUY` | `buy` |

### Utility traits
- None

### Used by models (casts)
- `CarAdvisement.operation`
- `PropertyAdvisement.operation`

---

## `Modules\Classifieds\Enums\StudyLevelEnum`

**File:** `Modules/Classifieds/Enums/StudyLevelEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `DIPLOMA` | `diploma` |
| `BACHELOR` | `bachelor` |
| `MASTER` | `master` |
| `PHD` | `phd` |
| `CERTIFICATE` | `certificate` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\HasTranslations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `InstituteAdvisement.study_level`

---

## `Modules\Classifieds\Enums\StudyTypeEnum`

**File:** `Modules/Classifieds/Enums/StudyTypeEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `ONSITE` | `onsite` |
| `ONLINE` | `online` |
| `HYBRID` | `hybrid` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\HasTranslations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `InstituteAdvisement.study_type`

---

## `Modules\Classifieds\Enums\UsageStatusEnum`

**File:** `Modules/Classifieds/Enums/UsageStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `NEW` | `new` |
| `USED` | `used` |
| `NOT_SPECIFIED` | `not_specified` |

### Utility traits
- None

### Used by models (casts)
- `CarAdvisement.usage_status`

---

# Guarantor

## `Modules\Guarantor\Enums\AuthorizationTypeEnum`

**File:** `Modules/Guarantor/Enums/AuthorizationTypeEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `PowerOfAttorney` | `power_of_attorney` |
| `Agency` | `agency` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `GuarantorCompanyDetail.authorization_type`

---

## `Modules\Guarantor\Enums\GuarantorStatusEnum`

**File:** `Modules/Guarantor/Enums/GuarantorStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `New` | `new` |
| `PendingAdmin` | `pending_admin` |
| `ApprovedByAdmin` | `approved_by_admin` |
| `RejectedByAdmin` | `rejected_by_admin` |
| `Accepted` | `accepted` |
| `Rejected` | `rejected` |
| `InProgress` | `in_progress` |
| `Overdue` | `overdue` |
| `Ended` | `ended` |
| `Cancelled` | `cancelled` |
| `Refunded` | `refunded` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `GuarantorRequest.status`

---

## `Modules\Guarantor\Enums\GuarantorTypeEnum`

**File:** `Modules/Guarantor/Enums/GuarantorTypeEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Individual` | `individual` |
| `Company` | `company` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `GuarantorRequest.type`

---

## `Modules\Guarantor\Enums\InstallmentStatusEnum`

**File:** `Modules/Guarantor/Enums/InstallmentStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Pending` | `pending` |
| `Paid` | `paid` |
| `Released` | `released` |
| `Overdue` | `overdue` |
| `Refunded` | `refunded` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `GuarantorInstallment.status`

---

# Opportunity

## `Modules\Opportunity\Enums\OfferStatusEnum`

**File:** `Modules/Opportunity/Enums/OfferStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Pending` | `pending` |
| `Accepted` | `accepted` |
| `Rejected` | `rejected` |
| `Cancelled` | `cancelled` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `OpportunityOffer.status`

---

## `Modules\Opportunity\Enums\OpportunityStatusEnum`

**File:** `Modules/Opportunity/Enums/OpportunityStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `New` | `new` |
| `OfferAccepted` | `offer_accepted` |
| `InProgress` | `in_progress` |
| `Ended` | `ended` |
| `Cancelled` | `cancelled` |
| `Expired` | `expired` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `Opportunity.status`

---

# Orders

## `Modules\Orders\Enums\OfferStatusEnum`

**File:** `Modules/Orders/Enums/OfferStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Pending` | `pending` |
| `Accepted` | `accepted` |
| `Rejected` | `rejected` |
| `Cancelled` | `cancelled` |
| `Paid` | `paid` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `OrderOffer.status`
- `OrderOfferHistory.status`

---

## `Modules\Orders\Enums\OrderStatusEnum`

**File:** `Modules/Orders/Enums/OrderStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `New` | `new` |
| `Hold` | `hold` |
| `OfferProvided` | `offer_provided` |
| `PaymentCompleted` | `payment_completed` |
| `InProgress` | `in_progress` |
| `CancelledByProvider` | `cancelled_by_provider` |
| `CancelledByClient` | `cancelled_by_client` |
| `EndedByProvider` | `ended_by_provider` |
| `EndedByClient` | `ended_by_client` |
| `Refunded` | `refunded` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `Order.status`
- `OrderStatusHistory.status`

---

# Payment

## `Modules\Payment\Enums\PaymentDriverEnum`

**File:** `Modules/Payment/Enums/PaymentDriverEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `PayTabs` | `paytabs` |
| `Rajhi` | `rajhi` |
| `Testing` | `testing` |

### Utility traits
- None

### Used by models (casts)
- None detected in model casts (may still be used in validation, events, DTOs, or JS enum generation)

---

## `Modules\Payment\Enums\PaymentMethodEnum`

**File:** `Modules/Payment/Enums/PaymentMethodEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Offline` | `offline` |
| `Online` | `online` |

### Utility traits
- `App\Enums\Utilities\HasOperations`

### Used by models (casts)
- `TopUpRequest.payment_method`

---

## `Modules\Payment\Enums\PaymentStatusEnum`

**File:** `Modules/Payment/Enums/PaymentStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Pending` | `pending` |
| `Accepted` | `accepted` |
| `Canceled` | `canceled` |
| `Rejected` | `rejected` |

### Utility traits
- `App\Enums\Utilities\Collectable`
- `App\Enums\Utilities\HasOperations`
- `App\Enums\Utilities\Stringable`

### Used by models (casts)
- `Payment.status`
- `TopUpRequest.payment_status`

---

# Sms

## `Modules\Sms\Enums\SmsMessageType`

**File:** `Modules/Sms/Enums/SmsMessageType.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Otp` | `otp` |
| `Custom` | `custom` |

### Utility traits
- None

### Used by models (casts)
- None detected in model casts (may still be used in validation, events, DTOs, or JS enum generation)

---

# Support

## `Modules\Support\Enums\TicketSupportStatusEnum`

**File:** `Modules/Support/Enums/TicketSupportStatusEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Pending` | `pending` |
| `Open` | `open` |
| `Closed` | `closed` |

### Utility traits
- `App\Enums\Utilities\HasOperations`

### Used by models (casts)
- `TicketSupport.status`

---

# Wallet

## `Modules\Wallet\Enums\TransactionTypeEnum`

**File:** `Modules/Wallet/Enums/TransactionTypeEnum.php`  
**Backing type:** `string`

| Case | Value |
|---|---|
| `Credit` | `credit` |
| `Debit` | `debit` |
| `PendingCredit` | `pending_credit` |
| `PendingDebit` | `pending_debit` |

### Utility traits
- None

### Used by models (casts)
- None detected in model casts (may still be used in validation, events, DTOs, or JS enum generation)

---

# Utility Enum Traits

Located in `app/Enums/Utilities/`. Verified present and used by several App Core enums.

## `App\Enums\Utilities\Collectable`

**File:** `app/Enums/Utilities/Collectable.php`

Adds `collect()` to return an `Illuminate\Support\Collection` of all enum cases.

```php
MyEnum::collect(); // Collection of cases
```

## `App\Enums\Utilities\HasOperations`

**File:** `app/Enums/Utilities/HasOperations.php`

Comparison helpers:

- `is(self $enum): bool`
- `isNot(self $enum): bool`
- `isIn(array $enums): bool`
- `isNotIn(array $enums): bool`

```php
if ($status->is(UserStatusEnum::Active)) { /* ... */ }
```

## `App\Enums\Utilities\HasTranslations`

**File:** `app/Enums/Utilities/HasTranslations.php`

Requires `getTranslatableKey(): string` and exposes `translated()` via Laravel `trans()`.

**Current usage:** used by Classifieds enums `ElectronicConditionEnum`, `InstituteTypeEnum`, `StudyLevelEnum`, and `StudyTypeEnum`.

## `App\Enums\Utilities\Stringable`

**File:** `app/Enums/Utilities/Stringable.php`

Adds `toString()` which translates `str($this->value)->lower()`.

```php
$status->toString();
```

---

## Usage patterns

**Model casts:**

```php
protected function casts(): array
{
    return [
        'status' => UserStatusEnum::class,
    ];
}
```

**Validation:**

```php
'status' => ['required', Rule::enum(OrderStatusEnum::class)],
```

**Queries:**

```php
Order::where('status', OrderStatusEnum::New)->get();
```

## Notes

- Prefer module-owned enums under `Modules/{Module}/Enums` for domain status/types; App Core keeps cross-cutting user/provider/job/operation enums.
- Never hardcode status/type strings when an enum exists.
- `Modules\Orders\Enums\OfferStatusEnum` and `Modules\Opportunity\Enums\OfferStatusEnum` are **distinct** classes that share a short name — always import the FQCN for the domain you mean.
