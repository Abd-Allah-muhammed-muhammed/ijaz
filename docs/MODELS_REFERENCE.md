# Models Reference

Regenerated from the live codebase (`app/Models` + `Modules/*/Models`).

**Last verified: 2026-07-26, post-full-module-extraction**

Field types come from `$casts` / `casts()` when present; fillable attributes without a cast are marked `Unknown`. Table names are resolved via Eloquent `getTable()` (including irregular plurals and intentional typos).

## Scope

| Group | Models |
|---|---:|
| App Core | 9 |
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
| Support | 1 |
| Wallet | 4 |
| **Total** | **73** |

## Notable post-refactor quirks (verified)

- `Modules\Jobs\Concerns\HasJobs::jobs()` is **`MorphMany`** to `JobOffer` (not `MorphOne`). Used by `User` and `Provider`.
- `Modules\Geo\Models\CityTranslation::city()` is a working **`BelongsTo`** `City` relation.
- `Modules\Catalog\Models\PropertiyCategory` (and `PropertiyCategoryTranslation`, table `propertiy_categories`) — **intentional deferred typo** in class/table naming; do not "fix" without a planned rename migration.
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
| `email_verified_at` | `'datetime'` | cast |
| `image` | `Unknown` | - |
| `job` | `Unknown` | - |
| `language` | `Unknown` | - |
| `name` | `Unknown` | - |
| `online` | `'boolean'` | cast |
| `password` | `'hashed'` | cast |
| `phone` | `Unknown` | - |
| `root` | `'boolean'` | cast (not in $fillable) |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

### Traits
- `App\Support\HasBroadcastChannel`
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Spatie\Permission\Traits\HasRoles`
- `Illuminate\Notifications\Notifiable`

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
| `blocked_at` | `'datetime'` | cast |
| `blocked_until` | `'datetime'` | cast |
| `reason` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `authenticatable()` | `MorphTo` | `(morph)` |

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
| `online` | `'boolean'` | cast (not in $fillable) |
| `password` | `'hashed'` | cast |
| `phone` | `Unknown` | - |
| `profile_picture` | `Unknown` | - |
| `provider_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `provider()` | `BelongsTo` | `Provider` |
| `company()` | `HasOne` | `Provider` |

### Traits
- `Spatie\Permission\Traits\HasRoles`

### Enums / class casts
- None detected in casts

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
| `blocked_at` | `'datetime'` | cast |
| `blocked_until` | `'datetime'` | cast |
| `city_id` | `Unknown` | foreign key candidate |
| `code` | `Unknown` | - |
| `email` | `Unknown` | - |
| `iban` | `Unknown` | - |
| `language` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `logo` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `name` | `Unknown` | - |
| `password` | `'hashed'` | cast |
| `phone` | `Unknown` | - |
| `provider_type_id` | `Unknown` | foreign key candidate |
| `region_id` | `Unknown` | foreign key candidate |
| `status` | `ProviderStatusEnum` | cast |
| `tax_number` | `Unknown` | - |
| `website` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `providerType()` | `BelongsTo` | `ProviderType` |
| `city()` | `BelongsTo` | `City` |
| `region()` | `BelongsTo` | `Region` |
| `categorySkills()` | `HasMany` | `CategorySkill` |
| `skills()` | `BelongsToMany` | `Skill` |
| `providerCategories()` | `HasMany` | `ProviderCategory` |
| `categories()` | `BelongsToMany` | `Category` |
| `orders()` | `HasMany` | `Order` |
| `orderOffers()` | `HasMany` | `OrderOffer` |
| `jobs()` | `MorphMany` | `JobOffer` — via Modules\Jobs\Concerns\HasJobs (MorphMany, not MorphOne) |

### Traits
- `App\Traits\Blockable`
- `App\Support\HasBroadcastChannel`
- `Modules\Jobs\Concerns\HasJobs`
- `Modules\Payment\Traits\HasPayments`
- `App\Traits\HasReviews`
- `Spatie\Permission\Traits\HasRoles`
- `Modules\Wallet\Traits\HasWallet`
- `Spatie\MediaLibrary\InteractsWithMedia`
- `Illuminate\Notifications\Notifiable`

### Enums / class casts
- `status` → `ProviderStatusEnum`

---

## Model: RegisterVerificationCode

**Namespace:** `App\Models`  
**Table:** `register_verification_codes`  
**File:** `app/Models/RegisterVerificationCode.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `expires_at` | `'datetime'` | cast |
| `queryable` | `Unknown` | - |
| `token` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: Review

**Namespace:** `App\Models`  
**Table:** `reviews`  
**File:** `app/Models/Review.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `comment` | `Unknown` | - |
| `operation_id` | `Unknown` | foreign key candidate |
| `operation_type` | `Unknown` | - |
| `rating` | `Unknown` | - |
| `reviewee_id` | `Unknown` | foreign key candidate |
| `reviewee_type` | `Unknown` | - |
| `reviewer_id` | `Unknown` | foreign key candidate |
| `reviewer_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `reviewer()` | `MorphTo` | `(morph)` |
| `reviewee()` | `MorphTo` | `(morph)` |
| `operation()` | `MorphTo` | `(morph)` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: Setting

**Namespace:** `App\Models`  
**Table:** `settings`  
**File:** `app/Models/Setting.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `content` | `Unknown` | - |
| `group` | `Unknown` | - |
| `key` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

### Traits
- None detected

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
| `blocked_at` | `'datetime'` | cast |
| `blocked_until` | `'datetime'` | cast |
| `email` | `Unknown` | - |
| `email_verified_at` | `'datetime'` | cast (not in $fillable) |
| `f_name` | `Unknown` | - |
| `image` | `Unknown` | - |
| `l_name` | `Unknown` | - |
| `language` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `nationality_id` | `Unknown` | foreign key candidate |
| `password` | `'hashed'` | cast |
| `phone` | `Unknown` | - |
| `phone_verified_at` | `'datetime'` | cast (not in $fillable) |
| `player_id` | `Unknown` | foreign key candidate |
| `status` | `UserStatusEnum` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `nationality()` | `BelongsTo` | `Nationality` |
| `orders()` | `HasMany` | `Order` |
| `guarantorRequests()` | `MorphMany` | `GuarantorRequest` |
| `assignedGuarantorRequests()` | `MorphMany` | `GuarantorRequest` |
| `propertyAdvisements()` | `MorphMany` | `PropertyAdvisement` |
| `carAdvisements()` | `MorphMany` | `CarAdvisement` |
| `electronicAdvisements()` | `MorphMany` | `ElectronicAdvisement` |
| `instituteAdvisements()` | `MorphMany` | `InstituteAdvisement` |
| `receivedMessages()` | `MorphMany` | `ConversationMessage` |
| `unreadReceivedMessages()` | `MorphMany` | `ConversationMessage` |
| `sentMessages()` | `MorphMany` | `ConversationMessage` |
| `unreadSentMessages()` | `MorphMany` | `ConversationMessage` |
| `jobs()` | `MorphMany` | `JobOffer` — via Modules\Jobs\Concerns\HasJobs (MorphMany, not MorphOne) |

### Traits
- `App\Traits\Blockable`
- `Laravel\Sanctum\HasApiTokens`
- `App\Support\HasBroadcastChannel`
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Modules\Jobs\Concerns\HasJobs`
- `App\Traits\HasOTPs`
- `Modules\Payment\Traits\HasPayments`
- `Modules\Wallet\Traits\HasWallet`
- `Illuminate\Notifications\Notifiable`

### Enums / class casts
- `status` → `UserStatusEnum`

---

## Model: VerificationCode

**Namespace:** `App\Models`  
**Table:** `verification_codes`  
**File:** `app/Models/VerificationCode.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `expiration_activated` | `'bool'` | cast |
| `expire_at` | `'datetime'` | cast |
| `token` | `Unknown` | - |
| `type` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

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
| `is_active` | `'boolean'` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Astrotomic\Translatable\Translatable`

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
| `carBrand()` | `BelongsTo` | `CarBrand` |

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
| `parent_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent()` | `BelongsTo` | `__CLASS__` |
| `children()` | `HasMany` | `__CLASS__` |
| `childrenRecursive()` | `HasMany` | `__CLASS__` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Astrotomic\Translatable\Translatable`

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
| `car_brand_id` | `Unknown` | foreign key candidate |
| `image` | `Unknown` | - |
| `is_active` | `'boolean'` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `carBrand()` | `BelongsTo` | `CarBrand` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Astrotomic\Translatable\Translatable`

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
| `carType()` | `BelongsTo` | `CarType` |

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
| `parent_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent()` | `BelongsTo` | `DeviceCategory` |
| `children()` | `HasMany` | `DeviceCategory` |

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
| `deviceCategory()` | `BelongsTo` | `DeviceCategory` |

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
| `is_active` | `'boolean'` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

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
| `electronicBrand()` | `BelongsTo` | `ElectronicBrand` |

### Traits
- None detected

### Enums / class casts
- None detected in casts

---

## Model: PropertiyCategory

**Namespace:** `Modules\Catalog\Models`  
**Table:** `propertiy_categories`  
**File:** `Modules/Catalog/Models/PropertiyCategory.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `is_active` | `'boolean'` | cast |
| `parent_id` | `'integer'` | cast; foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent()` | `BelongsTo` | `PropertiyCategory` |
| `children()` | `HasMany` | `PropertiyCategory` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Astrotomic\Translatable\Translatable`

### Enums / class casts
- None detected in casts

---

## Model: PropertiyCategoryTranslation

**Namespace:** `Modules\Catalog\Models`  
**Table:** `propertiy_category_translations`  
**File:** `Modules/Catalog/Models/PropertiyCategoryTranslation.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `locale` | `Unknown` | - |
| `title` | `'string'` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `propertiyCategory()` | `BelongsTo` | `PropertiyCategory` |

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
| `is_active` | `'boolean'` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Astrotomic\Translatable\Translatable`

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
| `propertyType()` | `BelongsTo` | `PropertyType` |

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
| `parent_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent()` | `BelongsTo` | `self` |
| `children()` | `HasMany` | `self` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Astrotomic\Translatable\Translatable`

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
| `specialization()` | `BelongsTo` | `Specialization` |

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
| `last_message_at` | `'datetime'` | cast |
| `last_message_id` | `Unknown` | foreign key candidate |
| `operation_id` | `Unknown` | foreign key candidate |
| `operation_type` | `Unknown` | - |
| `user1_id` | `Unknown` | foreign key candidate |
| `user1_type` | `Unknown` | - |
| `user2_id` | `Unknown` | foreign key candidate |
| `user2_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `messages()` | `HasMany` | `ConversationMessage` |
| `lastMessage()` | `BelongsTo` | `ConversationMessage` |
| `user1()` | `MorphTo` | `(morph)` |
| `user2()` | `MorphTo` | `(morph)` |
| `operation()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

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
| `conversation_message_id` | `Unknown` | foreign key candidate |
| `filename` | `Unknown` | - |
| `path` | `Unknown` | - |
| `store` | `Unknown` | - |
| `type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `message()` | `BelongsTo` | `ConversationMessage` |

### Traits
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

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
| `conversation_id` | `Unknown` | foreign key candidate |
| `deleted_at` | `Unknown` | - |
| `has_attachments` | `Unknown` | - |
| `read_at` | `Unknown` | - |
| `read_by_id` | `Unknown` | foreign key candidate |
| `read_by_type` | `Unknown` | - |
| `receiver_id` | `Unknown` | foreign key candidate |
| `receiver_type` | `Unknown` | - |
| `sender_id` | `Unknown` | foreign key candidate |
| `sender_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `chat()` | `BelongsTo` | `Conversation` |
| `sender()` | `MorphTo` | `(morph)` |
| `receiver()` | `MorphTo` | `(morph)` |
| `attachments()` | `HasMany` | `ConversationAttachment` |
| `lastAttachment()` | `HasOne` | `ConversationAttachment` |
| `readBy()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Concerns\HasUuids`
- `Spatie\MediaLibrary\InteractsWithMedia`
- `Illuminate\Database\Eloquent\SoftDeletes`

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
| (none detected) | - | - |

### Traits
- `Laravel\Sanctum\HasApiTokens`
- `App\Support\HasBroadcastChannel`

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
| `car_brand_id` | `Unknown` | foreign key candidate |
| `car_category_id` | `Unknown` | foreign key candidate |
| `car_type_id` | `Unknown` | foreign key candidate |
| `city_id` | `Unknown` | foreign key candidate |
| `color` | `Unknown` | - |
| `description` | `Unknown` | - |
| `engine_size` | `Unknown` | - |
| `fuel_type` | `Unknown` | - |
| `image` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `mileage` | `'integer'` | cast |
| `normalized_description` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `operation` | `OperationEnum` | cast |
| `options` | `'array'` | cast |
| `phone` | `Unknown` | - |
| `price` | `'float'` | cast |
| `region_id` | `Unknown` | foreign key candidate |
| `show_price` | `'boolean'` | cast |
| `status` | `AdvisementStatusEnum` | cast |
| `title` | `Unknown` | - |
| `transmission` | `Unknown` | - |
| `usage_status` | `UsageStatusEnum` | cast |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |
| `year` | `'integer'` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `carBrand()` | `BelongsTo` | `CarBrand` |
| `carType()` | `BelongsTo` | `CarType` |
| `carCategory()` | `BelongsTo` | `CarCategory` |
| `city()` | `BelongsTo` | `City` |
| `region()` | `BelongsTo` | `Region` |
| `user()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `App\Support\HasNormalizedAttributes`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- `status` → `AdvisementStatusEnum`
- `operation` → `OperationEnum`
- `usage_status` → `UsageStatusEnum`

---

## Model: ElectronicAdvisement

**Namespace:** `Modules\Classifieds\Models`  
**Table:** `electronic_advisements`  
**File:** `Modules/Classifieds/Models/ElectronicAdvisement.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `address` | `Unknown` | - |
| `city_id` | `Unknown` | foreign key candidate |
| `color` | `Unknown` | - |
| `condition` | `ElectronicConditionEnum` | cast |
| `description` | `Unknown` | - |
| `device_category_id` | `Unknown` | foreign key candidate |
| `electronic_brand_id` | `Unknown` | foreign key candidate |
| `image` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `model_name` | `Unknown` | - |
| `normalized_description` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `options` | `'array'` | cast |
| `phone` | `Unknown` | - |
| `price` | `'float'` | cast |
| `region_id` | `Unknown` | foreign key candidate |
| `show_price` | `'boolean'` | cast |
| `status` | `AdvisementStatusEnum` | cast |
| `storage` | `Unknown` | - |
| `title` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `deviceCategory()` | `BelongsTo` | `DeviceCategory` |
| `electronicBrand()` | `BelongsTo` | `ElectronicBrand` |
| `city()` | `BelongsTo` | `City` |
| `region()` | `BelongsTo` | `Region` |
| `user()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `App\Support\HasNormalizedAttributes`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- `status` → `AdvisementStatusEnum`
- `condition` → `ElectronicConditionEnum`

---

## Model: InstituteAdvisement

**Namespace:** `Modules\Classifieds\Models`  
**Table:** `institute_advisements`  
**File:** `Modules/Classifieds/Models/InstituteAdvisement.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `address` | `Unknown` | - |
| `city_id` | `Unknown` | foreign key candidate |
| `course_url` | `Unknown` | - |
| `days_count` | `'integer'` | cast |
| `description` | `Unknown` | - |
| `discounted_price` | `'float'` | cast |
| `goals` | `Unknown` | - |
| `hours_count` | `'integer'` | cast |
| `image` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `normalized_description` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `options` | `'array'` | cast |
| `payment_notes` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `price` | `'float'` | cast |
| `quality_url` | `Unknown` | - |
| `region_id` | `Unknown` | foreign key candidate |
| `registration_end` | `'date'` | cast |
| `registration_start` | `'date'` | cast |
| `registration_url` | `Unknown` | - |
| `specialization_id` | `Unknown` | foreign key candidate |
| `status` | `AdvisementStatusEnum` | cast |
| `study_end` | `'date'` | cast |
| `study_level` | `StudyLevelEnum` | cast |
| `study_start` | `'date'` | cast |
| `study_type` | `StudyTypeEnum` | cast |
| `title` | `Unknown` | - |
| `type` | `InstituteTypeEnum` | cast |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |
| `website` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `specialization()` | `BelongsTo` | `Specialization` |
| `city()` | `BelongsTo` | `City` |
| `region()` | `BelongsTo` | `Region` |
| `user()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `App\Support\HasNormalizedAttributes`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- `status` → `AdvisementStatusEnum`
- `type` → `InstituteTypeEnum`
- `study_type` → `StudyTypeEnum`
- `study_level` → `StudyLevelEnum`

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
| `category_id` | `Unknown` | foreign key candidate |
| `city_id` | `Unknown` | foreign key candidate |
| `description` | `Unknown` | - |
| `facade` | `Unknown` | - |
| `halls_count` | `Unknown` | - |
| `image` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `license` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `normalized_description` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `operation` | `OperationEnum` | cast |
| `options` | `'array'` | cast |
| `phone` | `Unknown` | - |
| `price` | `'float'` | cast |
| `property_type_id` | `Unknown` | foreign key candidate |
| `region_id` | `Unknown` | foreign key candidate |
| `show_price` | `'boolean'` | cast |
| `status` | `AdvisementStatusEnum` | cast |
| `street_type` | `Unknown` | - |
| `street_width` | `Unknown` | - |
| `title` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `category()` | `BelongsTo` | `PropertiyCategory` |
| `propertyType()` | `BelongsTo` | `PropertyType` |
| `city()` | `BelongsTo` | `City` |
| `region()` | `BelongsTo` | `Region` |
| `user()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `App\Support\HasNormalizedAttributes`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- `status` → `AdvisementStatusEnum`
- `operation` → `OperationEnum`

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
| (none detected) | - | - |

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
| `page()` | `BelongsTo` | `Page` |

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
| _(none listed)_ | - | no `$fillable` / `casts()` detected |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| (none detected) | - | - |

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
| `question()` | `BelongsTo` | `Question` |

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
| `region_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `region()` | `BelongsTo` | `Region` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Astrotomic\Translatable\Translatable`

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
| `city_id` | `Unknown` | foreign key candidate |
| `locale` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `city()` | `BelongsTo` | `City` |

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
| `is_active` | `'boolean'` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `users()` | `HasMany` | `User` |

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
| `nationality_id` | `Unknown` | foreign key candidate |
| `normalized_name` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `nationality()` | `BelongsTo` | `Nationality` |

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
| _(none listed)_ | - | no `$fillable` / `casts()` detected |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `cities()` | `HasMany` | `City` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Astrotomic\Translatable\Translatable`

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
| `region_id` | `Unknown` | foreign key candidate |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `region()` | `BelongsTo` | `Region` |

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
| `authorization_type` | `AuthorizationTypeEnum` | cast |
| `authorized_id_number` | `Unknown` | - |
| `authorized_name` | `Unknown` | - |
| `city_id` | `Unknown` | foreign key candidate |
| `commercial_register` | `Unknown` | - |
| `company_name` | `Unknown` | - |
| `counterparty_account_holder` | `'encrypted'` | cast |
| `counterparty_iban` | `'encrypted'` | cast |
| `guarantor_request_id` | `Unknown` | foreign key candidate |
| `region_id` | `Unknown` | foreign key candidate |
| `requester_account_holder` | `'encrypted'` | cast |
| `requester_iban` | `'encrypted'` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `guarantorRequest()` | `BelongsTo` | `GuarantorRequest` |
| `region()` | `BelongsTo` | `Region` |
| `city()` | `BelongsTo` | `City` |

### Traits
- `Illuminate\Database\Eloquent\Concerns\HasUuids`
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- `authorization_type` → `AuthorizationTypeEnum`

---

## Model: GuarantorInstallment

**Namespace:** `Modules\Guarantor\Models`  
**Table:** `guarantor_installments`  
**File:** `Modules/Guarantor/Models/GuarantorInstallment.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `amount` | `'decimal:2'` | cast |
| `due_date` | `'date'` | cast |
| `guarantor_request_id` | `Unknown` | foreign key candidate |
| `order` | `Unknown` | - |
| `overdue_notified_at` | `'datetime'` | cast |
| `paid_at` | `'datetime'` | cast |
| `released_at` | `'datetime'` | cast |
| `status` | `InstallmentStatusEnum` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `guarantorRequest()` | `BelongsTo` | `GuarantorRequest` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

### Enums / class casts
- `status` → `InstallmentStatusEnum`

---

## Model: GuarantorRequest

**Namespace:** `Modules\Guarantor\Models`  
**Table:** `guarantor_requests`  
**File:** `Modules/Guarantor/Models/GuarantorRequest.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `admin_notes` | `Unknown` | - |
| `amount` | `'decimal:2'` | cast |
| `cancellation_reason` | `Unknown` | - |
| `cancelled_at` | `'datetime'` | cast |
| `counterparty_id` | `Unknown` | foreign key candidate |
| `counterparty_type` | `Unknown` | - |
| `description` | `Unknown` | - |
| `ended_at` | `'datetime'` | cast |
| `fees` | `'decimal:2'` | cast |
| `overdue_at` | `'datetime'` | cast |
| `project_type` | `Unknown` | - |
| `refunded_at` | `'datetime'` | cast |
| `rejected_at` | `'datetime'` | cast |
| `requester_id` | `Unknown` | foreign key candidate |
| `requester_signature` | `Unknown` | - |
| `requester_type` | `Unknown` | - |
| `status` | `GuarantorStatusEnum` | cast |
| `title` | `Unknown` | - |
| `total` | `'decimal:2'` | cast (not in $fillable) |
| `type` | `GuarantorTypeEnum` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `requester()` | `MorphTo` | `(morph)` |
| `counterparty()` | `MorphTo` | `(morph)` |
| `installments()` | `HasMany` | `GuarantorInstallment` |
| `companyDetail()` | `HasOne` | `GuarantorCompanyDetail` |
| `statusHistories()` | `HasMany` | `GuarantorStatusHistory` |
| `conversation()` | `MorphOne` | `Conversation` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\Concerns\HasUuids`
- `Spatie\MediaLibrary\InteractsWithMedia`
- `Illuminate\Database\Eloquent\SoftDeletes`

### Enums / class casts
- `status` → `GuarantorStatusEnum`
- `type` → `GuarantorTypeEnum`

---

## Model: GuarantorStatusHistory

**Namespace:** `Modules\Guarantor\Models`  
**Table:** `guarantor_status_histories`  
**File:** `Modules/Guarantor/Models/GuarantorStatusHistory.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `actor_id` | `Unknown` | foreign key candidate |
| `actor_type` | `Unknown` | - |
| `from_status` | `Unknown` | - |
| `guarantor_request_id` | `Unknown` | foreign key candidate |
| `notes` | `Unknown` | - |
| `reason` | `Unknown` | - |
| `to_status` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `guarantorRequest()` | `BelongsTo` | `GuarantorRequest` |
| `actor()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

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
| `city_id` | `Unknown` | foreign key candidate |
| `contact_number` | `Unknown` | - |
| `description` | `Unknown` | - |
| `expected_salary` | `Unknown` | - |
| `expired_at` | `'datetime'` | cast |
| `nationality_id` | `Unknown` | foreign key candidate |
| `region_id` | `Unknown` | foreign key candidate |
| `title` | `Unknown` | - |
| `type` | `JobTypeEnum` | cast |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user()` | `MorphTo` | `(morph)` |
| `city()` | `BelongsTo` | `City` |
| `region()` | `BelongsTo` | `Region` |
| `nationality()` | `BelongsTo` | `Nationality` |
| `jobOfferSkills()` | `HasMany` | `JobOfferSkill` |
| `skills()` | `BelongsToMany` | `Skill` |

### Traits
- `Spatie\MediaLibrary\InteractsWithMedia`

### Enums / class casts
- `type` → `JobTypeEnum`

---

## Model: JobOfferSkill

**Namespace:** `Modules\Jobs\Models`  
**Table:** `job_offer_skill`  
**File:** `Modules/Jobs/Models/JobOfferSkill.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `job_offer_id` | `Unknown` | foreign key candidate |
| `skill_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `jobOffer()` | `BelongsTo` | `JobOffer` |
| `skill()` | `BelongsTo` | `Skill` |

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
| `fees_type` | `CategoryFeesTypeEnum` | cast |
| `icon` | `Unknown` | - |
| `parent_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent()` | `BelongsTo` | `self` |
| `children()` | `HasMany` | `self` |
| `childrenRecursive()` | `HasMany` | `self` |
| `skills()` | `HasMany` | `Skill` |
| `categorySkills()` | `HasMany` | `CategorySkill` |
| `providerSkills()` | `HasManyThrough` | `Skill` |
| `providerTypes()` | `BelongsToMany` | `ProviderType` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Astrotomic\Translatable\Translatable`

### Enums / class casts
- `fees_type` → `CategoryFeesTypeEnum`

---

## Model: CategorySkill

**Namespace:** `Modules\Marketplace\Models`  
**Table:** `category_skill`  
**File:** `Modules/Marketplace/Models/CategorySkill.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `category_id` | `'integer'` | cast; foreign key candidate |
| `provider_id` | `'integer'` | cast; foreign key candidate |
| `skill_id` | `'integer'` | cast; foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `category()` | `BelongsTo` | `Category` |
| `skill()` | `BelongsTo` | `Skill` |
| `provider()` | `BelongsTo` | `Provider` |

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
| `category()` | `BelongsTo` | `Category` |

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
| `category_id` | `'integer'` | cast; foreign key candidate |
| `provider_id` | `'integer'` | cast; foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `category()` | `BelongsTo` | `Category` |
| `provider()` | `BelongsTo` | `Provider` |

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
| `files` | `'array'` | cast |
| `image` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `providers()` | `HasMany` | `Provider` |
| `categories()` | `BelongsToMany` | `Category` |

### Traits
- `Spatie\Permission\Traits\HasPermissions`
- `Astrotomic\Translatable\Translatable`

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
| `provider_type_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `providerType()` | `BelongsTo` | `ProviderType` |

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
| `category_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `category()` | `BelongsTo` | `Category` |

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
| `skill_id` | `Unknown` | foreign key candidate |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `skill()` | `BelongsTo` | `Skill` |

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
| `accepted_offer_id` | `Unknown` | foreign key candidate |
| `author_id` | `Unknown` | foreign key candidate |
| `author_type` | `Unknown` | - |
| `budget` | `'decimal:2'` | cast |
| `city_id` | `Unknown` | foreign key candidate |
| `description` | `Unknown` | - |
| `email` | `Unknown` | - |
| `expires_at` | `'datetime'` | cast |
| `phone` | `Unknown` | - |
| `region_id` | `Unknown` | foreign key candidate |
| `status` | `OpportunityStatusEnum` | cast |
| `title` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `author()` | `MorphTo` | `(morph)` |
| `offers()` | `HasMany` | `OpportunityOffer` |
| `acceptedOffer()` | `BelongsTo` | `OpportunityOffer` |
| `comments()` | `HasMany` | `OpportunityComment` |
| `conversation()` | `MorphOne` | `Conversation` |
| `region()` | `BelongsTo` | `Region` |
| `city()` | `BelongsTo` | `City` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\Concerns\HasUuids`
- `Spatie\MediaLibrary\InteractsWithMedia`
- `Illuminate\Database\Eloquent\SoftDeletes`

### Enums / class casts
- `status` → `OpportunityStatusEnum`

---

## Model: OpportunityComment

**Namespace:** `Modules\Opportunity\Models`  
**Table:** `opportunity_comments`  
**File:** `Modules/Opportunity/Models/OpportunityComment.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `author_id` | `Unknown` | foreign key candidate |
| `author_type` | `Unknown` | - |
| `body` | `Unknown` | - |
| `opportunity_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `opportunity()` | `BelongsTo` | `Opportunity` |
| `author()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\Concerns\HasUuids`
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
| `author_id` | `Unknown` | foreign key candidate |
| `author_type` | `Unknown` | - |
| `description` | `Unknown` | - |
| `opportunity_id` | `Unknown` | foreign key candidate |
| `price` | `'decimal:2'` | cast |
| `status` | `OfferStatusEnum` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `opportunity()` | `BelongsTo` | `Opportunity` |
| `author()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\Concerns\HasUuids`
- `Illuminate\Database\Eloquent\SoftDeletes`

### Enums / class casts
- `status` → `OfferStatusEnum`

---

# Orders

## Model: Order

**Namespace:** `Modules\Orders\Models`  
**Table:** `orders`  
**File:** `Modules/Orders/Models/Order.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `accepted_offer_id` | `Unknown` | foreign key candidate |
| `budget_end` | `Unknown` | - |
| `budget_start` | `Unknown` | - |
| `category_id` | `Unknown` | foreign key candidate |
| `city_id` | `Unknown` | foreign key candidate |
| `description` | `Unknown` | - |
| `expected_time` | `Unknown` | - |
| `price` | `Unknown` | - |
| `provider_fees` | `Unknown` | - |
| `provider_id` | `Unknown` | foreign key candidate |
| `provider_total` | `Unknown` | - |
| `region_id` | `Unknown` | foreign key candidate |
| `status` | `OrderStatusEnum` | cast |
| `title` | `Unknown` | - |
| `total_fees` | `Unknown` | - |
| `user_fees` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `user_total` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user()` | `BelongsTo` | `User` |
| `provider()` | `BelongsTo` | `Provider` |
| `category()` | `BelongsTo` | `Category` |
| `offers()` | `HasMany` | `OrderOffer` |
| `histories()` | `HasMany` | `OrderStatusHistory` |
| `acceptedOffer()` | `BelongsTo` | `OrderOffer` |
| `city()` | `BelongsTo` | `City` |
| `region()` | `BelongsTo` | `Region` |
| `orderSkills()` | `HasMany` | `OrderSkill` |
| `skills()` | `BelongsToMany` | `Skill` |
| `conversation()` | `MorphOne` | `Conversation` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\Concerns\HasUuids`
- `Spatie\MediaLibrary\InteractsWithMedia`
- `App\Traits\Reviewable`

### Enums / class casts
- `status` → `OrderStatusEnum`

---

## Model: OrderOffer

**Namespace:** `Modules\Orders\Models`  
**Table:** `order_offers`  
**File:** `Modules/Orders/Models/OrderOffer.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `category_id` | `Unknown` | foreign key candidate |
| `description` | `Unknown` | - |
| `order_id` | `Unknown` | foreign key candidate |
| `price` | `Unknown` | - |
| `provider_id` | `Unknown` | foreign key candidate |
| `status` | `OfferStatusEnum` | cast |
| `user_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `order()` | `BelongsTo` | `Order` |
| `user()` | `BelongsTo` | `User` |
| `provider()` | `BelongsTo` | `Provider` |
| `histories()` | `HasMany` | `OrderOfferHistory` |

### Traits
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

### Enums / class casts
- `status` → `OfferStatusEnum`

---

## Model: OrderOfferHistory

**Namespace:** `Modules\Orders\Models`  
**Table:** `order_offers_histories`  
**File:** `Modules/Orders/Models/OrderOfferHistory.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `description` | `Unknown` | - |
| `order_id` | `Unknown` | foreign key candidate |
| `order_offer_id` | `Unknown` | foreign key candidate |
| `price` | `Unknown` | - |
| `status` | `OfferStatusEnum` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `order()` | `BelongsTo` | `Order` |
| `orderOffer()` | `BelongsTo` | `OrderOffer` |

### Traits
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

### Enums / class casts
- `status` → `OfferStatusEnum`

---

## Model: OrderSkill

**Namespace:** `Modules\Orders\Models`  
**Table:** `order_skill`  
**File:** `Modules/Orders/Models/OrderSkill.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| _(none listed)_ | - | no `$fillable` / `casts()` detected |

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
| `order_id` | `Unknown` | foreign key candidate |
| `status` | `OrderStatusEnum` | cast |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `order()` | `BelongsTo` | `Order` |

### Traits
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

### Enums / class casts
- `status` → `OrderStatusEnum`

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
| `product_id` | `Unknown` | foreign key candidate |
| `product_type` | `Unknown` | - |
| `request` | `'array'` | cast |
| `response` | `'array'` | cast |
| `status` | `PaymentStatusEnum` | cast |
| `transaction_id` | `Unknown` | foreign key candidate |
| `url` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user()` | `MorphTo` | `(morph)` |
| `product()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

### Enums / class casts
- `status` → `PaymentStatusEnum`

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
| `operation_id` | `Unknown` | foreign key candidate |
| `operation_type` | `Unknown` | - |
| `status` | `TicketSupportStatusEnum` | cast |
| `title` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user()` | `MorphTo` | `(morph)` |
| `operation()` | `MorphTo` | `(morph)` |
| `chat()` | `MorphOne` | `Conversation` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`

### Enums / class casts
- `status` → `TicketSupportStatusEnum`

---

# Wallet

## Model: TopUpRequest

**Namespace:** `Modules\Wallet\Models`  
**Table:** `top_up_requests`  
**File:** `Modules/Wallet/Models/TopUpRequest.php`

### Fields
| Field | Type | Notes |
|---|---|---|
| `admin_id` | `Unknown` | foreign key candidate |
| `admin_notes` | `Unknown` | - |
| `amount` | `Unknown` | - |
| `payment_driver` | `Unknown` | - |
| `payment_method` | `PaymentMethodEnum` | cast |
| `payment_status` | `PaymentStatusEnum` | cast |
| `status` | `OperationStatusEnum` | cast |
| `transaction_id` | `Unknown` | foreign key candidate |
| `transaction_image` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `user_notes` | `Unknown` | - |
| `user_type` | `Unknown` | - |
| `wallet_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user()` | `MorphTo` | `(morph)` |
| `wallet()` | `BelongsTo` | `Wallet` |
| `admin()` | `BelongsTo` | `Admin` |
| `payment()` | `MorphOne` | `Payment` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

### Enums / class casts
- `payment_method` → `PaymentMethodEnum`
- `payment_status` → `PaymentStatusEnum`
- `status` → `OperationStatusEnum`

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
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `transactions()` | `HasMany` | `WalletTransaction` |
| `user()` | `MorphTo` | `(morph)` |

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
| `operation_id` | `Unknown` | foreign key candidate |
| `operation_type` | `Unknown` | - |
| `payment_id` | `Unknown` | foreign key candidate |
| `pending_credit` | `Unknown` | - |
| `pending_debit` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |
| `wallet_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `operation()` | `MorphTo` | `(morph)` |
| `wallet()` | `BelongsTo` | `Wallet` |
| `user()` | `MorphTo` | `(morph)` |

### Traits
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

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
| `admin_id` | `Unknown` | foreign key candidate |
| `admin_notes` | `Unknown` | - |
| `amount` | `Unknown` | - |
| `status` | `OperationStatusEnum` | cast |
| `user_id` | `Unknown` | foreign key candidate |
| `user_notes` | `Unknown` | - |
| `user_type` | `Unknown` | - |
| `wallet_id` | `Unknown` | foreign key candidate |

### Relationships
| Method | Type | Related Model |
|---|---|---|
| `user()` | `MorphTo` | `(morph)` |
| `wallet()` | `BelongsTo` | `Wallet` |
| `admin()` | `BelongsTo` | `Admin` |

### Traits
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Database\Eloquent\Concerns\HasUuids`

### Enums / class casts
- `status` → `OperationStatusEnum`

---
