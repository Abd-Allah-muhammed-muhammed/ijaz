# Models Reference

Regenerated from the live codebase (`app/Models` + `Modules/*/Models`).

**Last verified: 2026-07-27, post-Settings/Reviews/Otp/Notification consolidations**

Field types come from `$casts` / `casts()` when present; fillable attributes without a cast are marked `Unknown`. Table names are resolved via Eloquent `getTable()` (including irregular plurals and intentional typos).

## Scope

| Group | Models |
|---|---:|
| App Core | 6 |
| Catalog | 16 |
| Chat | 4 |
| Classifieds | 4 |
| Cms | 6 |
| Geo | 6 |
| Guarantor | 4 |
| Jobs | 2 |
| Marketplace | 8 |
| Opportunity | 3 |
| Orders | 5 |
| Payment | 1 |
| Reviews | 1 |
| Settings | 1 |
| Support | 1 |
| Wallet | 4 |
| **Total** | **72** |

## Notable post-refactor quirks (verified)

- `Modules\Jobs\Concerns\HasJobs::jobs()` is **`MorphMany`** to `JobOffer` (not `MorphOne`). Used by `User` and `Provider`.
- `Modules\Geo\Models\CityTranslation::city()` is a working **`BelongsTo`** `City` relation.
- ~~`PropertyCategoryTranslation.normalized_title` column exists but is **not populated on save**~~ — **RESOLVED**: saving hook + backfill migration on `fix/property-category-normalized-title`.
- Unified `App\Models\Otp` (UUID PK) replaces deleted `VerificationCode` / `RegisterVerificationCode` models; purposes via `App\Enums\Auth\OtpPurposeEnum`.
- `Modules\Settings\Models\Setting` owns platform settings (`is_public`, `SettingGroupEnum`); public catalog endpoint is `Modules\Settings\Http\Controllers\Api\V1\SettingController`.
- `Modules\Reviews\Models\Review` is polymorphic (reviewer/reviewee/operation); `HasReviews` concern applied to User/Provider (and order review flows).
- Several pivot-style models use singular table names (e.g. `job_offer_skill`, `category_skill`, `provider_category`, `order_skill`).

---

# App Core

## Model: Admin

**Namespace:** `App\Models`  
**Table:** `admins`  
**File:** `app/Models/Admin.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `address` | `Unknown` | - |
| `email` | `Unknown` | - |
| `email_verified_at` | `Unknown` | - |
| `image` | `Unknown` | - |
| `job` | `Unknown` | - |
| `language` | `Unknown` | - |
| `name` | `Unknown` | - |
| `online` | `Unknown` | - |
| `password` | `Unknown` | - |
| `phone` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `roles` | `MorphToMany` | `Spatie\Permission\Models\Role` |
| `permissions` | `MorphToMany` | `Spatie\Permission\Models\Permission` |
| `notifications` | `MorphMany` | `Illuminate\Notifications\DatabaseNotification` |
| `readNotifications` | `MorphMany` | `Illuminate\Notifications\DatabaseNotification` |
| `unreadNotifications` | `MorphMany` | `Illuminate\Notifications\DatabaseNotification` |

### Traits
- `App\Support\HasBroadcastChannel`
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Notifications\Notifiable`
- `Spatie\Permission\Traits\HasRoles`

### Enums / class casts
- None detected in casts

---

## Model: BlockHistory

**Namespace:** `App\Models`  
**Table:** `block_histories`  
**File:** `app/Models/BlockHistory.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `blocked_at` | `datetime` | cast |
| `blocked_until` | `datetime` | cast |
| `reason` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `authenticatable` | `MorphTo` | `App\Models\BlockHistory` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: Employee

**Namespace:** `App\Models`  
**Table:** `employees`  
**File:** `app/Models/Employee.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `address` | `Unknown` | - |
| `email` | `Unknown` | - |
| `id_image` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `name` | `Unknown` | - |
| `password` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `profile_picture` | `Unknown` | - |
| `provider_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `provider` | `BelongsTo` | `App\Models\Provider` |
| `company` | `HasOne` | `App\Models\Provider` |
| `roles` | `MorphToMany` | `Spatie\Permission\Models\Role` |
| `permissions` | `MorphToMany` | `Spatie\Permission\Models\Permission` |

### Traits
- `Spatie\Permission\Traits\HasRoles`

### Enums / class casts
- None detected in casts

---

## Model: Otp

**Namespace:** `App\Models`  
**Table:** `otps`  
**File:** `app/Models/Otp.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `expires_at` | `'datetime'` | cast |
| `phone` | `Unknown` | - |
| `purpose` | `App\Enums\Auth\OtpPurposeEnum` | cast |
| `subject_id` | `Unknown` | - |
| `subject_type` | `Unknown` | - |
| `token` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `subject` | `MorphTo` | (polymorphic subject) |

### Traits
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

### Enums / class casts
- `purpose` → `App\Enums\Auth\OtpPurposeEnum`
- `expires_at` → `datetime`

**Notes:** UUID primary key (`$incrementing = false`, `$keyType = 'string'`). Replaces deleted `VerificationCode` / `RegisterVerificationCode`. Helpers: `isExpired()`, `matches()`.

---

## Model: Provider

**Namespace:** `App\Models`  
**Table:** `providers`  
**File:** `app/Models/Provider.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `about` | `Unknown` | - |
| `address` | `Unknown` | - |
| `blocked_at` | `Unknown` | - |
| `blocked_until` | `Unknown` | - |
| `city_id` | `Unknown` | - |
| `code` | `Unknown` | - |
| `email` | `Unknown` | - |
| `iban` | `Unknown` | - |
| `language` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `logo` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `name` | `Unknown` | - |
| `password` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `provider_type_id` | `Unknown` | - |
| `region_id` | `Unknown` | - |
| `status` | `Unknown` | - |
| `tax_number` | `Unknown` | - |
| `website` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `providerType` | `BelongsTo` | `Modules\Marketplace\Models\ProviderType` |
| `city` | `BelongsTo` | `Modules\Geo\Models\City` |
| `region` | `BelongsTo` | `Modules\Geo\Models\Region` |
| `categorySkills` | `HasMany` | `Modules\Marketplace\Models\CategorySkill` |
| `skills` | `BelongsToMany` | `Modules\Marketplace\Models\Skill` |
| `providerCategories` | `HasMany` | `Modules\Marketplace\Models\ProviderCategory` |
| `categories` | `BelongsToMany` | `Modules\Marketplace\Models\Category` |
| `orders` | `HasMany` | `Modules\Orders\Models\Order` |
| `orderOffers` | `HasMany` | `Modules\Orders\Models\OrderOffer` |
| `blockHistories` | `MorphMany` | `App\Models\BlockHistory` |
| `latestBlockHistory` | `MorphOne` | `App\Models\BlockHistory` |
| `jobs` | `MorphMany` | `Modules\Jobs\Models\JobOffer` |
| `payments` | `MorphMany` | `Modules\Payment\Models\Payment` |
| `reviews` | `MorphMany` | `Modules\Reviews\Models\Review` |
| `opinions` | `MorphMany` | `Modules\Reviews\Models\Review` |
| `roles` | `MorphToMany` | `Spatie\Permission\Models\Role` |
| `permissions` | `MorphToMany` | `Spatie\Permission\Models\Permission` |
| `wallet` | `MorphOne` | `Modules\Wallet\Models\Wallet` |
| `withdrawRequests` | `MorphMany` | `Modules\Wallet\Models\WithdrawRequest` |
| `walletTransactions` | `MorphMany` | `Modules\Wallet\Models\WalletTransaction` |
| `topUpRequests` | `MorphMany` | `Modules\Wallet\Models\TopUpRequest` |
| `media` | `MorphMany` | `Spatie\MediaLibrary\MediaCollections\Models\Media` |
| `notifications` | `MorphMany` | `Illuminate\Notifications\DatabaseNotification` |
| `readNotifications` | `MorphMany` | `Illuminate\Notifications\DatabaseNotification` |
| `unreadNotifications` | `MorphMany` | `Illuminate\Notifications\DatabaseNotification` |

### Traits
- `App\Support\HasBroadcastChannel`
- `App\Traits\Blockable`
- `Illuminate\Notifications\Notifiable`
- `Modules\Jobs\Concerns\HasJobs`
- `Modules\Payment\Traits\HasPayments`
- `Modules\Reviews\Concerns\HasReviews`
- `Modules\Wallet\Traits\HasWallet`
- `Spatie\MediaLibrary\InteractsWithMedia`
- `Spatie\Permission\Traits\HasRoles`

### Enums / class casts
- None detected in casts

---

## Model: User

**Namespace:** `App\Models`  
**Table:** `users`  
**File:** `app/Models/User.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `blocked_at` | `Unknown` | - |
| `blocked_until` | `Unknown` | - |
| `email` | `Unknown` | - |
| `f_name` | `Unknown` | - |
| `image` | `Unknown` | - |
| `l_name` | `Unknown` | - |
| `language` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `nationality_id` | `Unknown` | - |
| `password` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `player_id` | `Unknown` | - |
| `status` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `nationality` | `BelongsTo` | `Modules\Geo\Models\Nationality` |
| `orders` | `HasMany` | `Modules\Orders\Models\Order` |
| `guarantorRequests` | `MorphMany` | `Modules\Guarantor\Models\GuarantorRequest` |
| `assignedGuarantorRequests` | `MorphMany` | `Modules\Guarantor\Models\GuarantorRequest` |
| `propertyAdvisements` | `MorphMany` | `Modules\Classifieds\Models\PropertyAdvisement` |
| `carAdvisements` | `MorphMany` | `Modules\Classifieds\Models\CarAdvisement` |
| `electronicAdvisements` | `MorphMany` | `Modules\Classifieds\Models\ElectronicAdvisement` |
| `instituteAdvisements` | `MorphMany` | `Modules\Classifieds\Models\InstituteAdvisement` |
| `receivedMessages` | `MorphMany` | `Modules\Chat\Models\ConversationMessage` |
| `unreadReceivedMessages` | `MorphMany` | `Modules\Chat\Models\ConversationMessage` |
| `sentMessages` | `MorphMany` | `Modules\Chat\Models\ConversationMessage` |
| `unreadSentMessages` | `MorphMany` | `Modules\Chat\Models\ConversationMessage` |
| `blockHistories` | `MorphMany` | `App\Models\BlockHistory` |
| `latestBlockHistory` | `MorphOne` | `App\Models\BlockHistory` |
| `tokens` | `MorphMany` | `Laravel\Sanctum\PersonalAccessToken` |
| `jobs` | `MorphMany` | `Modules\Jobs\Models\JobOffer` |
| `otps` | `MorphMany` | `App\Models\Otp` |
| `verificationCodes` | `MorphMany` | `App\Models\Otp` |
| `emailVerificationCode` | `MorphOne` | `App\Models\Otp` |
| `phoneVerificationCode` | `MorphOne` | `App\Models\Otp` |
| `passwordVerificationCode` | `MorphOne` | `App\Models\Otp` |
| `loginVerificationCode` | `MorphOne` | `App\Models\Otp` |
| `passwordRestCode` | `MorphOne` | `App\Models\Otp` |
| `payments` | `MorphMany` | `Modules\Payment\Models\Payment` |
| `reviews` | `MorphMany` | `Modules\Reviews\Models\Review` |
| `opinions` | `MorphMany` | `Modules\Reviews\Models\Review` |
| `wallet` | `MorphOne` | `Modules\Wallet\Models\Wallet` |
| `withdrawRequests` | `MorphMany` | `Modules\Wallet\Models\WithdrawRequest` |
| `walletTransactions` | `MorphMany` | `Modules\Wallet\Models\WalletTransaction` |
| `topUpRequests` | `MorphMany` | `Modules\Wallet\Models\TopUpRequest` |
| `notifications` | `MorphMany` | `Illuminate\Notifications\DatabaseNotification` |
| `readNotifications` | `MorphMany` | `Illuminate\Notifications\DatabaseNotification` |
| `unreadNotifications` | `MorphMany` | `Illuminate\Notifications\DatabaseNotification` |

### Traits
- `App\Support\HasBroadcastChannel`
- `App\Traits\Blockable`
- `App\Traits\HasOTPs`
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Notifications\Notifiable`
- `Laravel\Sanctum\HasApiTokens`
- `Modules\Jobs\Concerns\HasJobs`
- `Modules\Payment\Traits\HasPayments`
- `Modules\Reviews\Concerns\HasReviews`
- `Modules\Wallet\Traits\HasWallet`

### Enums / class casts
- None detected in casts

---

# Catalog

## Model: CarBrand

**Namespace:** `Modules\Catalog\Models`  
**Table:** `car_brands`  
**File:** `Modules/Catalog/Models/CarBrand.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `image` | `Unknown` | - |
| `is_active` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `translation` | `HasOne` | `Modules\Catalog\Models\CarBrandTranslation` |
| `translations` | `HasMany` | `Modules\Catalog\Models\CarBrandTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

## Model: CarBrandTranslation

**Namespace:** `Modules\Catalog\Models`  
**Table:** `car_brand_translations`  
**File:** `Modules/Catalog/Models/CarBrandTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `name` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `carBrand` | `BelongsTo` | `Modules\Catalog\Models\CarBrand` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: CarCategory

**Namespace:** `Modules\Catalog\Models`  
**Table:** `car_categories`  
**File:** `Modules/Catalog/Models/CarCategory.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `icon` | `Unknown` | - |
| `parent_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent` | `BelongsTo` | `Modules\Catalog\Models\CarCategory` |
| `children` | `HasMany` | `Modules\Catalog\Models\CarCategory` |
| `childrenRecursive` | `HasMany` | `Modules\Catalog\Models\CarCategory` |
| `translation` | `HasOne` | `Modules\Catalog\Models\CarCategoryTranslation` |
| `translations` | `HasMany` | `Modules\Catalog\Models\CarCategoryTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

## Model: CarCategoryTranslation

**Namespace:** `Modules\Catalog\Models`  
**Table:** `car_category_translations`  
**File:** `Modules/Catalog/Models/CarCategoryTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: CarType

**Namespace:** `Modules\Catalog\Models`  
**Table:** `car_types`  
**File:** `Modules/Catalog/Models/CarType.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `car_brand_id` | `Unknown` | - |
| `image` | `Unknown` | - |
| `is_active` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `carBrand` | `BelongsTo` | `Modules\Catalog\Models\CarBrand` |
| `translation` | `HasOne` | `Modules\Catalog\Models\CarTypeTranslation` |
| `translations` | `HasMany` | `Modules\Catalog\Models\CarTypeTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

## Model: CarTypeTranslation

**Namespace:** `Modules\Catalog\Models`  
**Table:** `car_type_translations`  
**File:** `Modules/Catalog/Models/CarTypeTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `name` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `carType` | `BelongsTo` | `Modules\Catalog\Models\CarType` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: DeviceCategory

**Namespace:** `Modules\Catalog\Models`  
**Table:** `device_categories`  
**File:** `Modules/Catalog/Models/DeviceCategory.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `icon` | `Unknown` | - |
| `parent_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent` | `BelongsTo` | `Modules\Catalog\Models\DeviceCategory` |
| `children` | `HasMany` | `Modules\Catalog\Models\DeviceCategory` |
| `translation` | `HasOne` | `Modules\Catalog\Models\DeviceCategoryTranslation` |
| `translations` | `HasMany` | `Modules\Catalog\Models\DeviceCategoryTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`

### Enums / class casts
- None detected in casts

---

## Model: DeviceCategoryTranslation

**Namespace:** `Modules\Catalog\Models`  
**Table:** `device_category_translations`  
**File:** `Modules/Catalog/Models/DeviceCategoryTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `deviceCategory` | `BelongsTo` | `Modules\Catalog\Models\DeviceCategory` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: ElectronicBrand

**Namespace:** `Modules\Catalog\Models`  
**Table:** `electronic_brands`  
**File:** `Modules/Catalog/Models/ElectronicBrand.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `image` | `Unknown` | - |
| `is_active` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `translation` | `HasOne` | `Modules\Catalog\Models\ElectronicBrandTranslation` |
| `translations` | `HasMany` | `Modules\Catalog\Models\ElectronicBrandTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`

### Enums / class casts
- None detected in casts

---

## Model: ElectronicBrandTranslation

**Namespace:** `Modules\Catalog\Models`  
**Table:** `electronic_brand_translations`  
**File:** `Modules/Catalog/Models/ElectronicBrandTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `name` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `electronicBrand` | `BelongsTo` | `Modules\Catalog\Models\ElectronicBrand` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: PropertyCategory

**Namespace:** `Modules\Catalog\Models`  
**Table:** `property_categories`  
**File:** `Modules/Catalog/Models/PropertyCategory.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `is_active` | `Unknown` | - |
| `parent_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent` | `BelongsTo` | `Modules\Catalog\Models\PropertyCategory` |
| `children` | `HasMany` | `Modules\Catalog\Models\PropertyCategory` |
| `translation` | `HasOne` | `Modules\Catalog\Models\PropertyCategoryTranslation` |
| `translations` | `HasMany` | `Modules\Catalog\Models\PropertyCategoryTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

## Model: PropertyCategoryTranslation

**Namespace:** `Modules\Catalog\Models`  
**Table:** `property_category_translations`  
**File:** `Modules/Catalog/Models/PropertyCategoryTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `propertyCategory` | `BelongsTo` | `Modules\Catalog\Models\PropertyCategory` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: PropertyType

**Namespace:** `Modules\Catalog\Models`  
**Table:** `property_types`  
**File:** `Modules/Catalog/Models/PropertyType.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `is_active` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `translation` | `HasOne` | `Modules\Catalog\Models\PropertyTypeTranslation` |
| `translations` | `HasMany` | `Modules\Catalog\Models\PropertyTypeTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

## Model: PropertyTypeTranslation

**Namespace:** `Modules\Catalog\Models`  
**Table:** `property_type_translations`  
**File:** `Modules/Catalog/Models/PropertyTypeTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `name` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `propertyType` | `BelongsTo` | `Modules\Catalog\Models\PropertyType` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: Specialization

**Namespace:** `Modules\Catalog\Models`  
**Table:** `specializations`  
**File:** `Modules/Catalog/Models/Specialization.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `icon` | `Unknown` | - |
| `parent_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent` | `BelongsTo` | `Modules\Catalog\Models\Specialization` |
| `children` | `HasMany` | `Modules\Catalog\Models\Specialization` |
| `translation` | `HasOne` | `Modules\Catalog\Models\SpecializationTranslation` |
| `translations` | `HasMany` | `Modules\Catalog\Models\SpecializationTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

## Model: SpecializationTranslation

**Namespace:** `Modules\Catalog\Models`  
**Table:** `specialization_translations`  
**File:** `Modules/Catalog/Models/SpecializationTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `specialization` | `BelongsTo` | `Modules\Catalog\Models\Specialization` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

# Chat

## Model: Conversation

**Namespace:** `Modules\Chat\Models`  
**Table:** `conversations`  
**File:** `Modules/Chat/Models/Conversation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `last_message_at` | `Unknown` | - |
| `last_message_id` | `Unknown` | - |
| `operation_id` | `Unknown` | - |
| `operation_type` | `Unknown` | - |
| `user1_id` | `Unknown` | - |
| `user1_type` | `Unknown` | - |
| `user2_id` | `Unknown` | - |
| `user2_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `messages` | `HasMany` | `Modules\Chat\Models\ConversationMessage` |
| `lastMessage` | `BelongsTo` | `Modules\Chat\Models\ConversationMessage` |
| `user1` | `MorphTo` | `Modules\Chat\Models\Conversation` |
| `user2` | `MorphTo` | `Modules\Chat\Models\Conversation` |
| `operation` | `MorphTo` | `Modules\Chat\Models\Conversation` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: ConversationAttachment

**Namespace:** `Modules\Chat\Models`  
**Table:** `conversation_attachments`  
**File:** `Modules/Chat/Models/ConversationAttachment.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `conversation_message_id` | `Unknown` | - |
| `filename` | `Unknown` | - |
| `path` | `Unknown` | - |
| `store` | `Unknown` | - |
| `type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `message` | `BelongsTo` | `Modules\Chat\Models\ConversationMessage` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: ConversationMessage

**Namespace:** `Modules\Chat\Models`  
**Table:** `conversation_messages`  
**File:** `Modules/Chat/Models/ConversationMessage.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `content` | `Unknown` | - |
| `conversation_id` | `Unknown` | - |
| `deleted_at` | `Unknown` | - |
| `has_attachments` | `Unknown` | - |
| `read_at` | `Unknown` | - |
| `read_by_id` | `Unknown` | - |
| `read_by_type` | `Unknown` | - |
| `receiver_id` | `Unknown` | - |
| `receiver_type` | `Unknown` | - |
| `sender_id` | `Unknown` | - |
| `sender_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `chat` | `BelongsTo` | `Modules\Chat\Models\Conversation` |
| `sender` | `MorphTo` | `Modules\Chat\Models\ConversationMessage` |
| `receiver` | `MorphTo` | `Modules\Chat\Models\ConversationMessage` |
| `attachments` | `HasMany` | `Modules\Chat\Models\ConversationAttachment` |
| `lastAttachment` | `HasOne` | `Modules\Chat\Models\ConversationAttachment` |
| `readBy` | `MorphTo` | `Modules\Chat\Models\ConversationMessage` |
| `media` | `MorphMany` | `Spatie\MediaLibrary\MediaCollections\Models\Media` |

### Traits
- `Illuminate\Database\Eloquent\SoftDeletes`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- None detected in casts

---

## Model: System

**Namespace:** `Modules\Chat\Models`  
**Table:** `systems`  
**File:** `Modules/Chat/Models/System.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `name` | `Unknown` | - |
| `online` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `tokens` | `MorphMany` | `Laravel\Sanctum\PersonalAccessToken` |

### Traits
- `App\Support\HasBroadcastChannel`
- `Laravel\Sanctum\HasApiTokens`

### Enums / class casts
- None detected in casts

---

# Classifieds

## Model: CarAdvisement

**Namespace:** `Modules\Classifieds\Models`  
**Table:** `car_advisements`  
**File:** `Modules/Classifieds/Models/CarAdvisement.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `address` | `Unknown` | - |
| `car_brand_id` | `Unknown` | - |
| `car_category_id` | `Unknown` | - |
| `car_type_id` | `Unknown` | - |
| `city_id` | `Unknown` | - |
| `color` | `Unknown` | - |
| `description` | `Unknown` | - |
| `engine_size` | `Unknown` | - |
| `fuel_type` | `Unknown` | - |
| `image` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `mileage` | `integer` | cast |
| `normalized_description` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `operation` | `Modules\Classifieds\Enums\OperationEnum` | cast |
| `options` | `array` | cast |
| `phone` | `Unknown` | - |
| `price` | `float` | cast |
| `region_id` | `Unknown` | - |
| `show_price` | `boolean` | cast |
| `status` | `Modules\Classifieds\Enums\AdvisementStatusEnum` | cast |
| `title` | `Unknown` | - |
| `transmission` | `Unknown` | - |
| `usage_status` | `Modules\Classifieds\Enums\UsageStatusEnum` | cast |
| `user_id` | `Unknown` | - |
| `user_type` | `Unknown` | - |
| `year` | `integer` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `carBrand` | `BelongsTo` | `Modules\Catalog\Models\CarBrand` |
| `carType` | `BelongsTo` | `Modules\Catalog\Models\CarType` |
| `carCategory` | `BelongsTo` | `Modules\Catalog\Models\CarCategory` |
| `city` | `BelongsTo` | `Modules\Geo\Models\City` |
| `region` | `BelongsTo` | `Modules\Geo\Models\Region` |
| `user` | `MorphTo` | `Modules\Classifieds\Models\CarAdvisement` |
| `media` | `MorphMany` | `Spatie\MediaLibrary\MediaCollections\Models\Media` |

### Traits
- `App\Support\HasNormalizedAttributes`
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- `status` → `Modules\Classifieds\Enums\AdvisementStatusEnum`
- `operation` → `Modules\Classifieds\Enums\OperationEnum`
- `usage_status` → `Modules\Classifieds\Enums\UsageStatusEnum`

---

## Model: ElectronicAdvisement

**Namespace:** `Modules\Classifieds\Models`  
**Table:** `electronic_advisements`  
**File:** `Modules/Classifieds/Models/ElectronicAdvisement.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `address` | `Unknown` | - |
| `city_id` | `Unknown` | - |
| `color` | `Unknown` | - |
| `condition` | `Modules\Classifieds\Enums\ElectronicConditionEnum` | cast |
| `description` | `Unknown` | - |
| `device_category_id` | `Unknown` | - |
| `electronic_brand_id` | `Unknown` | - |
| `image` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `model_name` | `Unknown` | - |
| `normalized_description` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `options` | `array` | cast |
| `phone` | `Unknown` | - |
| `price` | `float` | cast |
| `region_id` | `Unknown` | - |
| `show_price` | `boolean` | cast |
| `status` | `Modules\Classifieds\Enums\AdvisementStatusEnum` | cast |
| `storage` | `Unknown` | - |
| `title` | `Unknown` | - |
| `user_id` | `Unknown` | - |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `deviceCategory` | `BelongsTo` | `Modules\Catalog\Models\DeviceCategory` |
| `electronicBrand` | `BelongsTo` | `Modules\Catalog\Models\ElectronicBrand` |
| `city` | `BelongsTo` | `Modules\Geo\Models\City` |
| `region` | `BelongsTo` | `Modules\Geo\Models\Region` |
| `user` | `MorphTo` | `Modules\Classifieds\Models\ElectronicAdvisement` |
| `media` | `MorphMany` | `Spatie\MediaLibrary\MediaCollections\Models\Media` |

### Traits
- `App\Support\HasNormalizedAttributes`
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- `status` → `Modules\Classifieds\Enums\AdvisementStatusEnum`
- `condition` → `Modules\Classifieds\Enums\ElectronicConditionEnum`

---

## Model: InstituteAdvisement

**Namespace:** `Modules\Classifieds\Models`  
**Table:** `institute_advisements`  
**File:** `Modules/Classifieds/Models/InstituteAdvisement.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `address` | `Unknown` | - |
| `city_id` | `Unknown` | - |
| `course_url` | `Unknown` | - |
| `days_count` | `integer` | cast |
| `description` | `Unknown` | - |
| `discounted_price` | `float` | cast |
| `goals` | `Unknown` | - |
| `hours_count` | `integer` | cast |
| `image` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `normalized_description` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `options` | `array` | cast |
| `payment_notes` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `price` | `float` | cast |
| `quality_url` | `Unknown` | - |
| `region_id` | `Unknown` | - |
| `registration_end` | `date` | cast |
| `registration_start` | `date` | cast |
| `registration_url` | `Unknown` | - |
| `specialization_id` | `Unknown` | - |
| `status` | `Modules\Classifieds\Enums\AdvisementStatusEnum` | cast |
| `study_end` | `date` | cast |
| `study_level` | `Modules\Classifieds\Enums\StudyLevelEnum` | cast |
| `study_start` | `date` | cast |
| `study_type` | `Modules\Classifieds\Enums\StudyTypeEnum` | cast |
| `title` | `Unknown` | - |
| `type` | `Modules\Classifieds\Enums\InstituteTypeEnum` | cast |
| `user_id` | `Unknown` | - |
| `user_type` | `Unknown` | - |
| `website` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `specialization` | `BelongsTo` | `Modules\Catalog\Models\Specialization` |
| `city` | `BelongsTo` | `Modules\Geo\Models\City` |
| `region` | `BelongsTo` | `Modules\Geo\Models\Region` |
| `user` | `MorphTo` | `Modules\Classifieds\Models\InstituteAdvisement` |
| `media` | `MorphMany` | `Spatie\MediaLibrary\MediaCollections\Models\Media` |

### Traits
- `App\Support\HasNormalizedAttributes`
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- `status` → `Modules\Classifieds\Enums\AdvisementStatusEnum`
- `type` → `Modules\Classifieds\Enums\InstituteTypeEnum`
- `study_type` → `Modules\Classifieds\Enums\StudyTypeEnum`
- `study_level` → `Modules\Classifieds\Enums\StudyLevelEnum`

---

## Model: PropertyAdvisement

**Namespace:** `Modules\Classifieds\Models`  
**Table:** `property_advisements`  
**File:** `Modules/Classifieds/Models/PropertyAdvisement.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `address` | `Unknown` | - |
| `age` | `Unknown` | - |
| `area` | `Unknown` | - |
| `bathrooms_count` | `Unknown` | - |
| `bedrooms_count` | `Unknown` | - |
| `category_id` | `Unknown` | - |
| `city_id` | `Unknown` | - |
| `description` | `Unknown` | - |
| `facade` | `Unknown` | - |
| `halls_count` | `Unknown` | - |
| `image` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `license` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `normalized_description` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `operation` | `Modules\Classifieds\Enums\OperationEnum` | cast |
| `options` | `array` | cast |
| `phone` | `Unknown` | - |
| `price` | `float` | cast |
| `property_type_id` | `Unknown` | - |
| `region_id` | `Unknown` | - |
| `show_price` | `boolean` | cast |
| `status` | `Modules\Classifieds\Enums\AdvisementStatusEnum` | cast |
| `street_type` | `Unknown` | - |
| `street_width` | `Unknown` | - |
| `title` | `Unknown` | - |
| `user_id` | `Unknown` | - |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `category` | `BelongsTo` | `Modules\Catalog\Models\PropertyCategory` |
| `propertyType` | `BelongsTo` | `Modules\Catalog\Models\PropertyType` |
| `city` | `BelongsTo` | `Modules\Geo\Models\City` |
| `region` | `BelongsTo` | `Modules\Geo\Models\Region` |
| `user` | `MorphTo` | `Modules\Classifieds\Models\PropertyAdvisement` |
| `media` | `MorphMany` | `Spatie\MediaLibrary\MediaCollections\Models\Media` |

### Traits
- `App\Support\HasNormalizedAttributes`
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- `status` → `Modules\Classifieds\Enums\AdvisementStatusEnum`
- `operation` → `Modules\Classifieds\Enums\OperationEnum`

---

# Cms

## Model: Banner

**Namespace:** `Modules\Cms\Models`  
**Table:** `banners`  
**File:** `Modules/Cms/Models/Banner.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `image` | `Unknown` | - |
| `link` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: Message

**Namespace:** `Modules\Cms\Models`  
**Table:** `messages`  
**File:** `Modules/Cms/Models/Message.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `content` | `Unknown` | - |
| `name` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: Page

**Namespace:** `Modules\Cms\Models`  
**Table:** `pages`  
**File:** `Modules/Cms/Models/Page.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `slug` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `translation` | `HasOne` | `Modules\Cms\Models\PageTranslation` |
| `translations` | `HasMany` | `Modules\Cms\Models\PageTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`

### Enums / class casts
- None detected in casts

---

## Model: PageTranslation

**Namespace:** `Modules\Cms\Models`  
**Table:** `page_translations`  
**File:** `Modules/Cms/Models/PageTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `content` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `page` | `BelongsTo` | `Modules\Cms\Models\Page` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: Question

**Namespace:** `Modules\Cms\Models`  
**Table:** `questions`  
**File:** `Modules/Cms/Models/Question.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| (none in $fillable / notable casts) | - | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `translation` | `HasOne` | `Modules\Cms\Models\QuestionTranslation` |
| `translations` | `HasMany` | `Modules\Cms\Models\QuestionTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`

### Enums / class casts
- None detected in casts

---

## Model: QuestionTranslation

**Namespace:** `Modules\Cms\Models`  
**Table:** `question_translations`  
**File:** `Modules/Cms/Models/QuestionTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `answer` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `question` | `BelongsTo` | `Modules\Cms\Models\Question` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

# Geo

## Model: City

**Namespace:** `Modules\Geo\Models`  
**Table:** `cities`  
**File:** `Modules/Geo/Models/City.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `region_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `region` | `BelongsTo` | `Modules\Geo\Models\Region` |
| `translation` | `HasOne` | `Modules\Geo\Models\CityTranslation` |
| `translations` | `HasMany` | `Modules\Geo\Models\CityTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

## Model: CityTranslation

**Namespace:** `Modules\Geo\Models`  
**Table:** `city_translations`  
**File:** `Modules/Geo/Models/CityTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `city_id` | `Unknown` | - |
| `locale` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `city` | `BelongsTo` | `Modules\Geo\Models\City` |

### Traits
- `App\Support\HasNormalizedAttributes`

### Enums / class casts
- None detected in casts

---

## Model: Nationality

**Namespace:** `Modules\Geo\Models`  
**Table:** `nationalities`  
**File:** `Modules/Geo/Models/Nationality.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `code` | `Unknown` | - |
| `icon` | `Unknown` | - |
| `is_active` | `boolean` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `users` | `HasMany` | `App\Models\User` |
| `translation` | `HasOne` | `Modules\Geo\Models\NationalityTranslation` |
| `translations` | `HasMany` | `Modules\Geo\Models\NationalityTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`

### Enums / class casts
- None detected in casts

---

## Model: NationalityTranslation

**Namespace:** `Modules\Geo\Models`  
**Table:** `nationality_translations`  
**File:** `Modules/Geo/Models/NationalityTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `name` | `Unknown` | - |
| `nationality_id` | `Unknown` | - |
| `normalized_name` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `nationality` | `BelongsTo` | `Modules\Geo\Models\Nationality` |

### Traits
- `App\Support\HasNormalizedAttributes`

### Enums / class casts
- None detected in casts

---

## Model: Region

**Namespace:** `Modules\Geo\Models`  
**Table:** `regions`  
**File:** `Modules/Geo/Models/Region.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `cities` | `HasMany` | `Modules\Geo\Models\City` |
| `translation` | `HasOne` | `Modules\Geo\Models\RegionTranslation` |
| `translations` | `HasMany` | `Modules\Geo\Models\RegionTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

## Model: RegionTranslation

**Namespace:** `Modules\Geo\Models`  
**Table:** `region_translations`  
**File:** `Modules/Geo/Models/RegionTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `region_id` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `region` | `BelongsTo` | `Modules\Geo\Models\Region` |

### Traits
- `App\Support\HasNormalizedAttributes`

### Enums / class casts
- None detected in casts

---

# Guarantor

## Model: GuarantorCompanyDetail

**Namespace:** `Modules\Guarantor\Models`  
**Table:** `guarantor_company_details`  
**File:** `Modules/Guarantor/Models/GuarantorCompanyDetail.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `authorization_type` | `Unknown` | - |
| `authorized_id_number` | `Unknown` | - |
| `authorized_name` | `Unknown` | - |
| `city_id` | `Unknown` | - |
| `commercial_register` | `Unknown` | - |
| `company_name` | `Unknown` | - |
| `counterparty_account_holder` | `Unknown` | - |
| `counterparty_iban` | `Unknown` | - |
| `guarantor_request_id` | `Unknown` | - |
| `region_id` | `Unknown` | - |
| `requester_account_holder` | `Unknown` | - |
| `requester_iban` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `guarantorRequest` | `BelongsTo` | `Modules\Guarantor\Models\GuarantorRequest` |
| `region` | `BelongsTo` | `Modules\Geo\Models\Region` |
| `city` | `BelongsTo` | `Modules\Geo\Models\City` |
| `media` | `MorphMany` | `Spatie\MediaLibrary\MediaCollections\Models\Media` |

### Traits
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- None detected in casts

---

## Model: GuarantorInstallment

**Namespace:** `Modules\Guarantor\Models`  
**Table:** `guarantor_installments`  
**File:** `Modules/Guarantor/Models/GuarantorInstallment.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `amount` | `Unknown` | - |
| `due_date` | `Unknown` | - |
| `guarantor_request_id` | `Unknown` | - |
| `order` | `Unknown` | - |
| `overdue_notified_at` | `Unknown` | - |
| `paid_at` | `Unknown` | - |
| `released_at` | `Unknown` | - |
| `status` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `guarantorRequest` | `BelongsTo` | `Modules\Guarantor\Models\GuarantorRequest` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

## Model: GuarantorRequest

**Namespace:** `Modules\Guarantor\Models`  
**Table:** `guarantor_requests`  
**File:** `Modules/Guarantor/Models/GuarantorRequest.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `admin_notes` | `Unknown` | - |
| `amount` | `Unknown` | - |
| `cancellation_reason` | `Unknown` | - |
| `cancelled_at` | `Unknown` | - |
| `counterparty_id` | `Unknown` | - |
| `counterparty_type` | `Unknown` | - |
| `description` | `Unknown` | - |
| `ended_at` | `Unknown` | - |
| `fees` | `Unknown` | - |
| `overdue_at` | `Unknown` | - |
| `project_type` | `Unknown` | - |
| `refunded_at` | `Unknown` | - |
| `rejected_at` | `Unknown` | - |
| `requester_id` | `Unknown` | - |
| `requester_signature` | `Unknown` | - |
| `requester_type` | `Unknown` | - |
| `status` | `Unknown` | - |
| `title` | `Unknown` | - |
| `type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `requester` | `MorphTo` | `Modules\Guarantor\Models\GuarantorRequest` |
| `counterparty` | `MorphTo` | `Modules\Guarantor\Models\GuarantorRequest` |
| `installments` | `HasMany` | `Modules\Guarantor\Models\GuarantorInstallment` |
| `companyDetail` | `HasOne` | `Modules\Guarantor\Models\GuarantorCompanyDetail` |
| `statusHistories` | `HasMany` | `Modules\Guarantor\Models\GuarantorStatusHistory` |
| `conversation` | `MorphOne` | `Modules\Chat\Models\Conversation` |
| `media` | `MorphMany` | `Spatie\MediaLibrary\MediaCollections\Models\Media` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\SoftDeletes`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- None detected in casts

---

## Model: GuarantorStatusHistory

**Namespace:** `Modules\Guarantor\Models`  
**Table:** `guarantor_status_histories`  
**File:** `Modules/Guarantor/Models/GuarantorStatusHistory.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `actor_id` | `Unknown` | - |
| `actor_type` | `Unknown` | - |
| `from_status` | `Unknown` | - |
| `guarantor_request_id` | `Unknown` | - |
| `notes` | `Unknown` | - |
| `reason` | `Unknown` | - |
| `to_status` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `guarantorRequest` | `BelongsTo` | `Modules\Guarantor\Models\GuarantorRequest` |
| `actor` | `MorphTo` | `Modules\Guarantor\Models\GuarantorStatusHistory` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

# Jobs

## Model: JobOffer

**Namespace:** `Modules\Jobs\Models`  
**Table:** `job_offers`  
**File:** `Modules/Jobs/Models/JobOffer.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `city_id` | `Unknown` | - |
| `contact_number` | `Unknown` | - |
| `description` | `Unknown` | - |
| `expected_salary` | `Unknown` | - |
| `expired_at` | `Unknown` | - |
| `nationality_id` | `Unknown` | - |
| `region_id` | `Unknown` | - |
| `title` | `Unknown` | - |
| `type` | `Unknown` | - |
| `user_id` | `Unknown` | - |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `MorphTo` | `Modules\Jobs\Models\JobOffer` |
| `city` | `BelongsTo` | `Modules\Geo\Models\City` |
| `region` | `BelongsTo` | `Modules\Geo\Models\Region` |
| `nationality` | `BelongsTo` | `Modules\Geo\Models\Nationality` |
| `jobOfferSkills` | `HasMany` | `Modules\Jobs\Models\JobOfferSkill` |
| `skills` | `BelongsToMany` | `Modules\Marketplace\Models\Skill` |
| `media` | `MorphMany` | `Spatie\MediaLibrary\MediaCollections\Models\Media` |

### Traits
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- None detected in casts

---

## Model: JobOfferSkill

**Namespace:** `Modules\Jobs\Models`  
**Table:** `job_offer_skill`  
**File:** `Modules/Jobs/Models/JobOfferSkill.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `job_offer_id` | `Unknown` | - |
| `skill_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `jobOffer` | `BelongsTo` | `Modules\Jobs\Models\JobOffer` |
| `skill` | `BelongsTo` | `Modules\Marketplace\Models\Skill` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

# Marketplace

## Model: Category

**Namespace:** `Modules\Marketplace\Models`  
**Table:** `categories`  
**File:** `Modules/Marketplace/Models/Category.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `fees` | `Unknown` | - |
| `fees_type` | `App\Enums\CategoryFeesTypeEnum` | cast |
| `icon` | `Unknown` | - |
| `parent_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent` | `BelongsTo` | `Modules\Marketplace\Models\Category` |
| `children` | `HasMany` | `Modules\Marketplace\Models\Category` |
| `childrenRecursive` | `HasMany` | `Modules\Marketplace\Models\Category` |
| `skills` | `HasMany` | `Modules\Marketplace\Models\Skill` |
| `categorySkills` | `HasMany` | `Modules\Marketplace\Models\CategorySkill` |
| `providerSkills` | `HasManyThrough` | `Modules\Marketplace\Models\Skill` |
| `providerTypes` | `BelongsToMany` | `Modules\Marketplace\Models\ProviderType` |
| `translation` | `HasOne` | `Modules\Marketplace\Models\CategoryTranslation` |
| `translations` | `HasMany` | `Modules\Marketplace\Models\CategoryTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- `fees_type` → `App\Enums\CategoryFeesTypeEnum`

---

## Model: CategorySkill

**Namespace:** `Modules\Marketplace\Models`  
**Table:** `category_skill`  
**File:** `Modules/Marketplace/Models/CategorySkill.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `category_id` | `integer` | cast |
| `provider_id` | `integer` | cast |
| `skill_id` | `integer` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `category` | `BelongsTo` | `Modules\Marketplace\Models\Category` |
| `skill` | `BelongsTo` | `Modules\Marketplace\Models\Skill` |
| `provider` | `BelongsTo` | `App\Models\Provider` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: CategoryTranslation

**Namespace:** `Modules\Marketplace\Models`  
**Table:** `category_translations`  
**File:** `Modules/Marketplace/Models/CategoryTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `description` | `Unknown` | - |
| `locale` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `category` | `BelongsTo` | `Modules\Marketplace\Models\Category` |

### Traits
- `App\Support\HasNormalizedAttributes`

### Enums / class casts
- None detected in casts

---

## Model: ProviderCategory

**Namespace:** `Modules\Marketplace\Models`  
**Table:** `provider_category`  
**File:** `Modules/Marketplace/Models/ProviderCategory.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `category_id` | `integer` | cast |
| `provider_id` | `integer` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `category` | `BelongsTo` | `Modules\Marketplace\Models\Category` |
| `provider` | `BelongsTo` | `App\Models\Provider` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: ProviderType

**Namespace:** `Modules\Marketplace\Models`  
**Table:** `provider_types`  
**File:** `Modules/Marketplace/Models/ProviderType.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `files` | `Unknown` | - |
| `image` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `providers` | `HasMany` | `App\Models\Provider` |
| `categories` | `BelongsToMany` | `Modules\Marketplace\Models\Category` |
| `permissions` | `MorphToMany` | `Spatie\Permission\Models\Permission` |
| `translation` | `HasOne` | `Modules\Marketplace\Models\ProviderTypeTranslation` |
| `translations` | `HasMany` | `Modules\Marketplace\Models\ProviderTypeTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`
- `Spatie\Permission\Traits\HasPermissions`

### Enums / class casts
- None detected in casts

---

## Model: ProviderTypeTranslation

**Namespace:** `Modules\Marketplace\Models`  
**Table:** `provider_type_translations`  
**File:** `Modules/Marketplace/Models/ProviderTypeTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `description` | `Unknown` | - |
| `locale` | `Unknown` | - |
| `name` | `Unknown` | - |
| `provider_type_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `providerType` | `BelongsTo` | `Modules\Marketplace\Models\ProviderType` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: Skill

**Namespace:** `Modules\Marketplace\Models`  
**Table:** `skills`  
**File:** `Modules/Marketplace/Models/Skill.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `category_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `category` | `BelongsTo` | `Modules\Marketplace\Models\Category` |
| `translation` | `HasOne` | `Modules\Marketplace\Models\SkillTranslation` |
| `translations` | `HasMany` | `Modules\Marketplace\Models\SkillTranslation` |

### Traits
- `Astrotomic\Translatable\Translatable`

### Enums / class casts
- None detected in casts

---

## Model: SkillTranslation

**Namespace:** `Modules\Marketplace\Models`  
**Table:** `skill_translations`  
**File:** `Modules/Marketplace/Models/SkillTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `skill_id` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `skill` | `BelongsTo` | `Modules\Marketplace\Models\Skill` |

### Traits
- `App\Support\HasNormalizedAttributes`

### Enums / class casts
- None detected in casts

---

# Opportunity

## Model: Opportunity

**Namespace:** `Modules\Opportunity\Models`  
**Table:** `opportunities`  
**File:** `Modules/Opportunity/Models/Opportunity.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `accepted_offer_id` | `Unknown` | - |
| `author_id` | `Unknown` | - |
| `author_type` | `Unknown` | - |
| `budget` | `Unknown` | - |
| `city_id` | `Unknown` | - |
| `description` | `Unknown` | - |
| `email` | `Unknown` | - |
| `expires_at` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `region_id` | `Unknown` | - |
| `status` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `author` | `MorphTo` | `Modules\Opportunity\Models\Opportunity` |
| `offers` | `HasMany` | `Modules\Opportunity\Models\OpportunityOffer` |
| `acceptedOffer` | `BelongsTo` | `Modules\Opportunity\Models\OpportunityOffer` |
| `comments` | `HasMany` | `Modules\Opportunity\Models\OpportunityComment` |
| `conversation` | `MorphOne` | `Modules\Chat\Models\Conversation` |
| `region` | `BelongsTo` | `Modules\Geo\Models\Region` |
| `city` | `BelongsTo` | `Modules\Geo\Models\City` |
| `media` | `MorphMany` | `Spatie\MediaLibrary\MediaCollections\Models\Media` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\SoftDeletes`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- None detected in casts

---

## Model: OpportunityComment

**Namespace:** `Modules\Opportunity\Models`  
**Table:** `opportunity_comments`  
**File:** `Modules/Opportunity/Models/OpportunityComment.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `author_id` | `Unknown` | - |
| `author_type` | `Unknown` | - |
| `body` | `Unknown` | - |
| `opportunity_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `opportunity` | `BelongsTo` | `Modules\Opportunity\Models\Opportunity` |
| `author` | `MorphTo` | `Modules\Opportunity\Models\OpportunityComment` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\SoftDeletes`

### Enums / class casts
- None detected in casts

---

## Model: OpportunityOffer

**Namespace:** `Modules\Opportunity\Models`  
**Table:** `opportunity_offers`  
**File:** `Modules/Opportunity/Models/OpportunityOffer.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `author_id` | `Unknown` | - |
| `author_type` | `Unknown` | - |
| `description` | `Unknown` | - |
| `opportunity_id` | `Unknown` | - |
| `price` | `Unknown` | - |
| `status` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `opportunity` | `BelongsTo` | `Modules\Opportunity\Models\Opportunity` |
| `author` | `MorphTo` | `Modules\Opportunity\Models\OpportunityOffer` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\SoftDeletes`

### Enums / class casts
- None detected in casts

---

# Orders

## Model: Order

**Namespace:** `Modules\Orders\Models`  
**Table:** `orders`  
**File:** `Modules/Orders/Models/Order.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `accepted_offer_id` | `Unknown` | - |
| `budget_end` | `Unknown` | - |
| `budget_start` | `Unknown` | - |
| `category_id` | `Unknown` | - |
| `city_id` | `Unknown` | - |
| `description` | `Unknown` | - |
| `expected_time` | `Unknown` | - |
| `price` | `Unknown` | - |
| `provider_fees` | `Unknown` | - |
| `provider_id` | `Unknown` | - |
| `provider_total` | `Unknown` | - |
| `region_id` | `Unknown` | - |
| `status` | `Unknown` | - |
| `title` | `Unknown` | - |
| `total_fees` | `Unknown` | - |
| `user_fees` | `Unknown` | - |
| `user_id` | `Unknown` | - |
| `user_total` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `BelongsTo` | `App\Models\User` |
| `provider` | `BelongsTo` | `App\Models\Provider` |
| `category` | `BelongsTo` | `Modules\Marketplace\Models\Category` |
| `offers` | `HasMany` | `Modules\Orders\Models\OrderOffer` |
| `histories` | `HasMany` | `Modules\Orders\Models\OrderStatusHistory` |
| `acceptedOffer` | `BelongsTo` | `Modules\Orders\Models\OrderOffer` |
| `city` | `BelongsTo` | `Modules\Geo\Models\City` |
| `region` | `BelongsTo` | `Modules\Geo\Models\Region` |
| `orderSkills` | `HasMany` | `Modules\Orders\Models\OrderSkill` |
| `skills` | `BelongsToMany` | `Modules\Marketplace\Models\Skill` |
| `conversation` | `MorphOne` | `Modules\Chat\Models\Conversation` |
| `media` | `MorphMany` | `Spatie\MediaLibrary\MediaCollections\Models\Media` |
| `reviews` | `MorphMany` | `Modules\Reviews\Models\Review` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Modules\Reviews\Concerns\Reviewable`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- None detected in casts

---

## Model: OrderOffer

**Namespace:** `Modules\Orders\Models`  
**Table:** `order_offers`  
**File:** `Modules/Orders/Models/OrderOffer.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `category_id` | `Unknown` | - |
| `description` | `Unknown` | - |
| `order_id` | `Unknown` | - |
| `price` | `Unknown` | - |
| `provider_id` | `Unknown` | - |
| `status` | `Unknown` | - |
| `user_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `order` | `BelongsTo` | `Modules\Orders\Models\Order` |
| `user` | `BelongsTo` | `App\Models\User` |
| `provider` | `BelongsTo` | `App\Models\Provider` |
| `histories` | `HasMany` | `Modules\Orders\Models\OrderOfferHistory` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: OrderOfferHistory

**Namespace:** `Modules\Orders\Models`  
**Table:** `order_offers_histories`  
**File:** `Modules/Orders/Models/OrderOfferHistory.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `description` | `Unknown` | - |
| `order_id` | `Unknown` | - |
| `order_offer_id` | `Unknown` | - |
| `price` | `Unknown` | - |
| `status` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `order` | `BelongsTo` | `Modules\Orders\Models\Order` |
| `orderOffer` | `BelongsTo` | `Modules\Orders\Models\OrderOffer` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: OrderSkill

**Namespace:** `Modules\Orders\Models`  
**Table:** `order_skill`  
**File:** `Modules/Orders/Models/OrderSkill.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| (none in $fillable / notable casts) | - | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: OrderStatusHistory

**Namespace:** `Modules\Orders\Models`  
**Table:** `order_status_histories`  
**File:** `Modules/Orders/Models/OrderStatusHistory.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `order_id` | `Unknown` | - |
| `status` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `order` | `BelongsTo` | `Modules\Orders\Models\Order` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

# Payment

## Model: Payment

**Namespace:** `Modules\Payment\Models`  
**Table:** `payments`  
**File:** `Modules/Payment/Models/Payment.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `amount` | `Unknown` | - |
| `driver` | `Unknown` | - |
| `message` | `Unknown` | - |
| `product_id` | `Unknown` | - |
| `product_type` | `Unknown` | - |
| `request` | `array` | cast |
| `response` | `array` | cast |
| `status` | `Modules\Payment\Enums\PaymentStatusEnum` | cast |
| `transaction_id` | `Unknown` | - |
| `url` | `Unknown` | - |
| `user_id` | `Unknown` | - |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `MorphTo` | `Modules\Payment\Models\Payment` |
| `product` | `MorphTo` | `Modules\Payment\Models\Payment` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- `status` → `Modules\Payment\Enums\PaymentStatusEnum`

---

# Reviews

## Model: Review

**Namespace:** `Modules\Reviews\Models`  
**Table:** `reviews`  
**File:** `Modules/Reviews/Models/Review.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `comment` | `Unknown` | - |
| `operation_id` | `Unknown` | - |
| `operation_type` | `Unknown` | - |
| `rating` | `Unknown` | - |
| `reviewee_id` | `Unknown` | - |
| `reviewee_type` | `Unknown` | - |
| `reviewer_id` | `Unknown` | - |
| `reviewer_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `reviewer` | `MorphTo` | (polymorphic reviewer) |
| `reviewee` | `MorphTo` | (polymorphic reviewee) |
| `operation` | `MorphTo` | (polymorphic operation, e.g. Order) |

### Traits
- None detected

### Enums / class casts
- None detected in casts

**Notes:** Consumed via `Modules\Reviews\Concerns\HasReviews` on `User` / `Provider`. Dashboard routes under `Modules/Reviews/Routes/dashboard.php`. Nested on `ProviderResource` (contract: `ProviderReviewApiContractTest`).

---

# Settings

## Model: Setting

**Namespace:** `Modules\Settings\Models`  
**Table:** `settings`  
**File:** `Modules/Settings/Models/Setting.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `content` | `Unknown` | - |
| `group` | `Modules\Settings\Enums\SettingGroupEnum` | cast |
| `is_public` | `'boolean'` | cast |
| `key` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

### Traits
- None detected

### Enums / class casts
- `group` → `Modules\Settings\Enums\SettingGroupEnum`
- `is_public` → `boolean`

**Notes:** Public catalog endpoint `GET /api/v1/catalog/settings` via `Modules\Settings\Http\Controllers\Api\V1\SettingController` (returns public settings only).

---

# Support

## Model: TicketSupport

**Namespace:** `Modules\Support\Models`  
**Table:** `ticket_supports`  
**File:** `Modules/Support/Models/TicketSupport.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `message` | `Unknown` | - |
| `operation_id` | `Unknown` | - |
| `operation_type` | `Unknown` | - |
| `status` | `Unknown` | - |
| `title` | `Unknown` | - |
| `user_id` | `Unknown` | - |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `MorphTo` | `Modules\Support\Models\TicketSupport` |
| `operation` | `MorphTo` | `Modules\Support\Models\TicketSupport` |
| `chat` | `MorphOne` | `Modules\Chat\Models\Conversation` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

# Wallet

## Model: TopUpRequest

**Namespace:** `Modules\Wallet\Models`  
**Table:** `top_up_requests`  
**File:** `Modules/Wallet/Models/TopUpRequest.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `admin_id` | `Unknown` | - |
| `admin_notes` | `Unknown` | - |
| `amount` | `Unknown` | - |
| `payment_driver` | `Unknown` | - |
| `payment_method` | `Unknown` | - |
| `payment_status` | `Unknown` | - |
| `status` | `Unknown` | - |
| `transaction_id` | `Unknown` | - |
| `transaction_image` | `Unknown` | - |
| `user_id` | `Unknown` | - |
| `user_notes` | `Unknown` | - |
| `user_type` | `Unknown` | - |
| `wallet_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `MorphTo` | `Modules\Wallet\Models\TopUpRequest` |
| `wallet` | `BelongsTo` | `Modules\Wallet\Models\Wallet` |
| `admin` | `BelongsTo` | `App\Models\Admin` |
| `payment` | `MorphOne` | `Modules\Payment\Models\Payment` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

## Model: Wallet

**Namespace:** `Modules\Wallet\Models`  
**Table:** `wallets`  
**File:** `Modules/Wallet/Models/Wallet.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `balance` | `Unknown` | - |
| `credit` | `Unknown` | - |
| `debit` | `Unknown` | - |
| `pending_credit` | `Unknown` | - |
| `pending_debit` | `Unknown` | - |
| `total_earning` | `Unknown` | - |
| `total_spent` | `Unknown` | - |
| `user_id` | `Unknown` | - |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `transactions` | `HasMany` | `Modules\Wallet\Models\WalletTransaction` |
| `user` | `MorphTo` | `Modules\Wallet\Models\Wallet` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: WalletTransaction

**Namespace:** `Modules\Wallet\Models`  
**Table:** `wallet_transactions`  
**File:** `Modules/Wallet/Models/WalletTransaction.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `balance_after` | `Unknown` | - |
| `balance_before` | `Unknown` | - |
| `credit` | `Unknown` | - |
| `debit` | `Unknown` | - |
| `description` | `Unknown` | - |
| `operation_id` | `Unknown` | - |
| `operation_type` | `Unknown` | - |
| `payment_id` | `Unknown` | - |
| `pending_credit` | `Unknown` | - |
| `pending_debit` | `Unknown` | - |
| `user_id` | `Unknown` | - |
| `user_type` | `Unknown` | - |
| `wallet_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `operation` | `MorphTo` | `Modules\Wallet\Models\WalletTransaction` |
| `wallet` | `BelongsTo` | `Modules\Wallet\Models\Wallet` |
| `user` | `MorphTo` | `Modules\Wallet\Models\WalletTransaction` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: WithdrawRequest

**Namespace:** `Modules\Wallet\Models`  
**Table:** `withdraw_requests`  
**File:** `Modules/Wallet/Models/WithdrawRequest.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `admin_id` | `Unknown` | - |
| `admin_notes` | `Unknown` | - |
| `amount` | `Unknown` | - |
| `status` | `Unknown` | - |
| `user_id` | `Unknown` | - |
| `user_notes` | `Unknown` | - |
| `user_type` | `Unknown` | - |
| `wallet_id` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `MorphTo` | `Modules\Wallet\Models\WithdrawRequest` |
| `wallet` | `BelongsTo` | `Modules\Wallet\Models\Wallet` |
| `admin` | `BelongsTo` | `App\Models\Admin` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- None detected in casts

---

