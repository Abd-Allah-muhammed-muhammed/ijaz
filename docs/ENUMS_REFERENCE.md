# Enums Reference

Last verified from source: 2026-04-16

Extracted from `app/Enums/` directory. All enums are backed enums unless otherwise noted.

---

## `App\Enums\Advisements\AdvisementStatusEnum`
**File:** `app/Enums/Advisements/AdvisementStatusEnum.php`

| Case | Value | Description |
|---|---|---|
| `PUBLISHED` | `published` | Listing is publicly visible |
| `PENDING` | `pending` | Awaiting approval |
| `REJECTED` | `rejected` | Rejected by admin |
| `CLOSED` | `closed` | Listing closed by owner |

---

## `App\Enums\Advisements\OperationEnum`
**File:** `app/Enums/Advisements/OperationEnum.php`

| Case | Value | Description |
|---|---|---|
| `SALE` | `sale` | Selling operation |
| `RENT` | `rent` | Renting operation |
| `BUY` | `buy` | Buying operation |

---

## `App\Enums\Advisements\UsageStatusEnum`
**File:** `app/Enums/Advisements/UsageStatusEnum.php`

| Case | Value | Description |
|---|---|---|
| `NEW` | `new` | Brand new item |
| `USED` | `used` | Used item |
| `NOT_SPECIFIED` | `not_specified` | Status not specified |

---

## `App\Enums\CategoryFeesTypeEnum`
**File:** `app/Enums/CategoryFeesTypeEnum.php`

| Case | Value | Description |
|---|---|---|
| `INHERITED` | `inherited` | Fees inherited from parent category |
| `FIXED` | `fixed` | Fixed fee amount |
| `PERCENTAGE` | `percentage` | Percentage-based fee |

---

## `App\Enums\Chat\ChatEventEnum`
**File:** `app/Enums/Chat/ChatEventEnum.php`

| Case | Value | Description |
|---|---|---|
| `New_Message` | `new-message` | New message in chat |
| `Chat_Updated` | `chat-updated` | Chat conversation updated |

---

## `App\Enums\Jobs\JobTypeEnum`
**File:** `app/Enums/Jobs/JobTypeEnum.php`

| Case | Value | Description |
|---|---|---|
| `Governmental` | `1` | Government job |
| `Private` | `2` | Private sector job |

---

## `App\Enums\OperationStatusEnum`
**File:** `app/Enums/OperationStatusEnum.php`

| Case | Value | Description |
|---|---|---|
| `Pending` | `pending` | Awaiting action |
| `Rejected` | `rejected` | Operation rejected |
| `Approved` | `approved` | Operation approved |

---

## `App\Enums\Order\OfferStatusEnum`
**File:** `app/Enums/Order/OfferStatusEnum.php`

| Case | Value | Description |
|---|---|---|
| `Pending` | `pending` | Offer awaiting response |
| `Accepted` | `accepted` | Offer accepted |
| `Rejected` | `rejected` | Offer rejected |
| `Cancelled` | `cancelled` | Offer cancelled |
| `Paid` | `paid` | Payment processed |

---

## `App\Enums\Order\OrderStatusEnum`
**File:** `app/Enums/Order/OrderStatusEnum.php`

| Case | Value | Description |
|---|---|---|
| `New` | `new` | Newly created order |
| `Hold` | `hold` | Order on hold |
| `OfferProvided` | `offer_provided` | Provider submitted offer |
| `PaymentCompleted` | `payment_completed` | Payment completed |
| `InProgress` | `in_progress` | Work in progress |
| `CancelledByProvider` | `cancelled_by_provider` | Cancelled by provider |
| `CancelledByClient` | `cancelled_by_client` | Cancelled by client |
| `EndedByProvider` | `ended_by_provider` | Ended by provider |
| `EndedByClient` | `ended_by_client` | Ended by client |
| `Refunded` | `refunded` | Payment refunded |

---

## `App\Enums\Payment\PaymentDriverEnum`
**File:** `app/Enums/Payment/PaymentDriverEnum.php`

| Case | Value | Description |
|---|---|---|
| `PayTabs` | `paytabs` | PayTabs gateway (production) |
| `Testing` | `testing` | Testing gateway (development) |

⚠️ Note: Testing driver has hardcoded fee logic; if this expands, config-driven behavior would be cleaner.

---

## `App\Enums\Payment\PaymentMethodEnum`
**File:** `app/Enums/Payment/PaymentMethodEnum.php`

| Case | Value | Description |
|---|---|---|
| `Offline` | `offline` | Offline payment (transfer, check, etc.) |
| `Online` | `online` | Online payment gateway |

---

## `App\Enums\Payment\PaymentStatusEnum`
**File:** `app/Enums/Payment/PaymentStatusEnum.php`

| Case | Value | Description |
|---|---|---|
| `Pending` | `pending` | Payment awaiting processing |
| `Accepted` | `accepted` | Payment accepted by gateway |
| `Canceled` | `canceled` | Payment cancelled |
| `Rejected` | `rejected` | Payment rejected by gateway |

---

## `App\Enums\Providers\ProviderStatusEnum`
**File:** `app/Enums/Providers/ProviderStatusEnum.php`

| Case | Value | Description |
|---|---|---|
| `Pending` | `pending` | Awaiting approval |
| `Approved` | `approved` | Approved to operate |
| `Suspended` | `suspended` | Temporarily suspended |
| `Rejected` | `rejected` | Application rejected |
| `Blocked` | `blocked` | Account blocked |

---

## `App\Enums\ProviderTypeFilesEnum`
**File:** `app/Enums/ProviderTypeFilesEnum.php`

| Case | Value | Description |
|---|---|---|
| `ID_IMAGE` | `id_image` | Government ID document |
| `COMMERCIAL_RECORD` | `commercial_record` | Business license |
| `FREELANCER_CERTIFICATION` | `freelancer_certification` | Freelancer certification |
| `IBAN_CERTIFICATION` | `iban_certification` | Bank account verification |
| `LICENSE_TO_PRACTICE_LAW` | `license_to_practice_law` | Legal practice license |

---

## `App\Enums\SupportTickets\TicketSupportStatusEnum`
**File:** `app/Enums/SupportTickets/TicketSupportStatusEnum.php`

| Case | Value | Description |
|---|---|---|
| `Pending` | `pending` | Ticket newly created |
| `Open` | `open` | Ticket being worked on |
| `Closed` | `closed` | Ticket resolved |

---

## `App\Enums\Users\UserStatusEnum`
**File:** `app/Enums/Users/UserStatusEnum.php`

| Case | Value | Description |
|---|---|---|
| `Active` | `active` | Account active |
| `Blocked` | `blocked` | Account blocked |
| `Deleted` | `deleted` | Account deleted (soft-deleted) |

---

## Utility Enum Traits

The following traits are available for use with enums and are used throughout the codebase:

### `Collectable`
Adds a `collect()` method to gather enum cases into a collection.

**Usage:**
```php
class MyEnum extends Enum {
    use Collectable;
}

$collected = MyEnum::collect(); // Returns Collection of all cases
```

### `HasOperations`
Adds comparison helpers for enum matching.

**Methods:**
- `is(Enum $compare)` - Check if this case equals the comparison
- `isNot(Enum $compare)` - Check if this case does not equal the comparison
- `isIn(array $cases)` - Check if this case is in the given array
- `isNotIn(array $cases)` - Check if this case is not in the given array

**Usage:**
```php
if ($status->is(OrderStatusEnum::Completed)) {
    // Handle completion
}
```

### `HasTranslations`
Adds `translated()` method based on an abstract translation key.

**Usage:**
```php
$label = $status->translated(); // Returns translated label
```

### `Stringable`
Adds `toString()` translation helper for converting enum cases to readable strings.

**Usage:**
```php
$label = $status->toString(); // Returns string representation
```

---

## Enum Usage Patterns

**In Model Casts:**
```php
protected $casts = [
    'status' => UserStatusEnum::class,
    'payment_status' => PaymentStatusEnum::class,
];
```

**In Validation Rules:**
```php
'status' => ['required', Rule::enum(OrderStatusEnum::class)],
```

**In Database Queries:**
```php
$active_orders = Order::where('status', OrderStatusEnum::New->value)->get();
// Or with enum directly (if using backed enums):
$active_orders = Order::where('status', OrderStatusEnum::New)->get();
```

---

## Notes

- All enums are in `app/Enums/` with subdirectories organized by domain (Advisements, Order, Payment, etc.)
- Enums use backing values (strings or integers) to store in the database
- The codebase relies heavily on enums for type safety instead of hardcoded status strings
- ⚠️ Never hardcode status strings; always use the corresponding enum from `app/Enums/`
