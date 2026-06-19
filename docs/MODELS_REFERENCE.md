# Models Reference

Generated from source files in app/Models. Field types are taken from casts/PHP types where available; otherwise marked Unknown.

Last verified from source: 2026-04-16

---

# Model: Admin

**Table:** `admins`  
**File:** `app/Models/Admin.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `name` | `Unknown` | - |
| `email` | `Unknown` | - |
| `password` | `'hashed'` | cast |
| `phone` | `Unknown` | - |
| `address` | `Unknown` | - |
| `job` | `Unknown` | - |
| `image` | `Unknown` | - |
| `email_verified_at` | `'datetime'` | cast |
| `online` | `'boolean'` | cast |
| `language` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- `App\Traits\HasBroadcastChanel`

## Enums Used
- None detected in casts()

---

# Model: Banner

**Table:** `banners`  
**File:** `app/Models/Banner.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `link` | `Unknown` | - |
| `image` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: BlockHistory

**Table:** `block_historys`  
**File:** `app/Models/BlockHistory.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `blocked_at` | `Unknown` | - |
| `blocked_until` | `Unknown` | - |
| `reason` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: CarAdvisement

**Table:** `car_advisements`  
**File:** `app/Models/CarAdvisement.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `description` | `Unknown` | - |
| `normalized_description` | `Unknown` | - |
| `image` | `Unknown` | - |
| `status` | `Unknown` | - |
| `operation` | `Unknown` | - |
| `usage_status` | `Unknown` | - |
| `user_type` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `car_brand_id` | `Unknown` | foreign key candidate |
| `car_type_id` | `Unknown` | foreign key candidate |
| `car_category_id` | `Unknown` | foreign key candidate |
| `year` | `Unknown` | - |
| `mileage` | `Unknown` | - |
| `transmission` | `Unknown` | - |
| `fuel_type` | `Unknown` | - |
| `engine_size` | `Unknown` | - |
| `color` | `Unknown` | - |
| `price` | `Unknown` | - |
| `show_price` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `address` | `Unknown` | - |
| `city_id` | `Unknown` | foreign key candidate |
| `region_id` | `Unknown` | foreign key candidate |
| `options` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `carBrand` | `BelongsTo` | `CarBrand` |
| `carType` | `BelongsTo` | `CarType` |
| `carCategory` | `BelongsTo` | `CarCategory` |
| `city` | `BelongsTo` | `City` |
| `region` | `BelongsTo` | `Region` |
| `user` | `MorphTo` | `Unknown` |

## Traits
- `App\Traits\HasNormalizedAttributes`

## Enums Used
- None detected in casts()

---

# Model: CarBrand

**Table:** `car_brands`  
**File:** `app/Models/CarBrand.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `is_active` | `'boolean'` | cast |
| `image` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: CarBrandTranslation

**Table:** `car_brand_translations`  
**File:** `app/Models/CarBrandTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `name` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `carBrand` | `BelongsTo` | `CarBrand` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: CarCategory

**Table:** `car_categorys`  
**File:** `app/Models/CarCategory.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `icon` | `Unknown` | - |
| `parent_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent` | `BelongsTo` | `Unknown` |
| `children` | `HasMany` | `Unknown` |
| `childrenRecursive` | `HasMany` | `Unknown` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: CarCategoryTranslation

**Table:** `car_category_translations`  
**File:** `app/Models/CarCategoryTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: CarType

**Table:** `car_types`  
**File:** `app/Models/CarType.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `is_active` | `'boolean'` | cast |
| `image` | `Unknown` | - |
| `car_brand_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `carBrand` | `BelongsTo` | `Unknown` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: CarTypeTranslation

**Table:** `car_type_translations`  
**File:** `app/Models/CarTypeTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `name` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `carType` | `BelongsTo` | `CarType` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Category

**Table:** `categorys`  
**File:** `app/Models/Category.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `icon` | `Unknown` | - |
| `parent_id` | `Unknown` | foreign key candidate |
| `fees` | `Unknown` | - |
| `fees_type` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `parent` | `BelongsTo` | `Unknown` |
| `children` | `HasMany` | `Unknown` |
| `childrenRecursive` | `HasMany` | `Unknown` |
| `skills` | `HasMany` | `Unknown` |
| `categorySkills` | `HasMany` | `CategorySkill` |
| `providerSkills` | `HasMany` | `ProviderType` |
| `providerTypes` | `BelongsToMany` | `Unknown` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: CategorySkill

**Table:** `category_skills`  
**File:** `app/Models/CategorySkill.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `category_id` | `Unknown` | foreign key candidate |
| `skill_id` | `Unknown` | foreign key candidate |
| `provider_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `category` | `BelongsTo` | `Category` |
| `skill` | `BelongsTo` | `Skill` |
| `provider` | `BelongsTo` | `Provider` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: CategoryTranslation

**Table:** `category_translations`  
**File:** `app/Models/CategoryTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `locale` | `Unknown` | - |
| `description` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `category` | `BelongsTo` | `Category` |

## Traits
- `App\Traits\HasNormalizedAttributes`

## Enums Used
- None detected in casts()

---

# Model: City

**Table:** `citys`  
**File:** `app/Models/City.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `region_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `region` | `BelongsTo` | `Region` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: CityTranslation

**Table:** `city_translations`  
**File:** `app/Models/CityTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `locale` | `Unknown` | - |
| `city_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `city` | `BelongsTo` | `Region` |

## Traits
- `App\Traits\HasNormalizedAttributes`

## Enums Used
- None detected in casts()

---

# Model: Conversation

**Table:** `conversations`  
**File:** `app/Models/Conversation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `user1_id` | `Unknown` | foreign key candidate |
| `user1_type` | `Unknown` | - |
| `user2_id` | `Unknown` | foreign key candidate |
| `user2_type` | `Unknown` | - |
| `last_message_id` | `Unknown` | foreign key candidate |
| `last_message_at` | `'datetime'` | cast |
| `operation_id` | `Unknown` | foreign key candidate |
| `operation_type` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `messages` | `HasMany` | `ConversationMessage` |
| `lastMessage` | `BelongsTo` | `ConversationMessage` |
| `user1` | `MorphTo` | `Unknown` |
| `user2` | `MorphTo` | `Unknown` |
| `operation` | `MorphTo` | `Unknown` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: ConversationAttachment

**Table:** `conversation_attachments`  
**File:** `app/Models/ConversationAttachment.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `conversation_message_id` | `Unknown` | foreign key candidate |
| `type` | `Unknown` | - |
| `filename` | `Unknown` | - |
| `path` | `Unknown` | - |
| `store` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `chat` | `BelongsTo` | `Conversation` |
| `chatMessage` | `BelongsTo` | `ConversationMessage` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: ConversationMessage

**Table:** `conversation_messages`  
**File:** `app/Models/ConversationMessage.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `sender_id` | `Unknown` | foreign key candidate |
| `sender_type` | `Unknown` | - |
| `content` | `Unknown` | - |
| `read_at` | `Unknown` | - |
| `read_by_id` | `Unknown` | foreign key candidate |
| `read_by_type` | `Unknown` | - |
| `conversation_id` | `Unknown` | foreign key candidate |
| `receiver_id` | `Unknown` | foreign key candidate |
| `receiver_type` | `Unknown` | - |
| `has_attachments` | `Unknown` | - |
| `deleted_at` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `chat` | `BelongsTo` | `Conversation` |
| `sender` | `MorphTo` | `Unknown` |
| `receiver` | `MorphTo` | `Unknown` |
| `attachments` | `HasMany` | `ConversationAttachment` |
| `lastAttachment` | `HasOne` | `ConversationAttachment` |
| `readBy` | `MorphTo` | `Unknown` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: DeviceCategory

**Table:** `device_categorys`  
**File:** `app/Models/DeviceCategory.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `icon` | `Unknown` | - |
| `parent_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: DeviceCategoryTranslation

**Table:** `device_category_translations`  
**File:** `app/Models/DeviceCategoryTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `deviceCategory` | `BelongsTo` | `DeviceCategory` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Employee

**Table:** `employees`  
**File:** `app/Models/Employee.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `name` | `Unknown` | - |
| `id_image` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `email` | `Unknown` | - |
| `address` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `provider_id` | `Unknown` | foreign key candidate |
| `profile_picture` | `Unknown` | - |
| `password` | `'hashed'` | cast |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `provider` | `BelongsTo` | `Provider` |
| `company` | `HasOne` | `Provider` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: JobOffer

**Table:** `job_offers`  
**File:** `app/Models/JobOffer.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |
| `title` | `Unknown` | - |
| `description` | `Unknown` | - |
| `expired_at` | `'datetime'` | cast |
| `contact_number` | `Unknown` | - |
| `city_id` | `Unknown` | foreign key candidate |
| `region_id` | `Unknown` | foreign key candidate |
| `nationality_id` | `Unknown` | foreign key candidate |
| `type` | `JobTypeEnum::class` | cast |
| `expected_salary` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `MorphTo` | `City` |
| `city` | `BelongsTo` | `Unknown` |
| `region` | `BelongsTo` | `Region` |
| `nationality` | `BelongsTo` | `Nationality` |
| `jobOfferSkills` | `HasMany` | `JobOfferSkill` |
| `skills` | `BelongsToMany` | `Skill` |

## Traits
- None detected

## Enums Used
- `type` => `JobTypeEnum::class`

---

# Model: JobOfferSkill

**Table:** `job_offer_skills`  
**File:** `app/Models/JobOfferSkill.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `job_offer_id` | `Unknown` | foreign key candidate |
| `skill_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `jobOffer` | `BelongsTo` | `JobOffer` |
| `skill` | `BelongsTo` | `Skill` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Message

**Table:** `messages`  
**File:** `app/Models/Message.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `name` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `title` | `Unknown` | - |
| `content` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Nationality

**Table:** `nationalitys`  
**File:** `app/Models/Nationality.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `code` | `Unknown` | - |
| `icon` | `Unknown` | - |
| `is_active` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `users` | `HasMany` | `User` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: NationalityTranslation

**Table:** `nationality_translations`  
**File:** `app/Models/NationalityTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `name` | `Unknown` | - |
| `normalized_name` | `Unknown` | - |
| `locale` | `Unknown` | - |
| `nationality_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `nationality` | `BelongsTo` | `Nationality` |

## Traits
- `App\Traits\HasNormalizedAttributes`

## Enums Used
- None detected in casts()

---

# Model: Order

**Table:** `orders`  
**File:** `app/Models/Order.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `Unknown` | - |
| `description` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `provider_id` | `Unknown` | foreign key candidate |
| `category_id` | `Unknown` | foreign key candidate |
| `price` | `Unknown` | - |
| `status` | `OrderStatusEnum::class` | cast |
| `expected_time` | `Unknown` | - |
| `budget_start` | `Unknown` | - |
| `budget_end` | `Unknown` | - |
| `accepted_offer_id` | `Unknown` | foreign key candidate |
| `city_id` | `Unknown` | foreign key candidate |
| `region_id` | `Unknown` | foreign key candidate |
| `user_fees` | `Unknown` | - |
| `provider_fees` | `Unknown` | - |
| `total_fees` | `Unknown` | - |
| `user_total` | `Unknown` | - |
| `provider_total` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `BelongsTo` | `User` |
| `provider` | `BelongsTo` | `Provider` |
| `category` | `BelongsTo` | `Category` |
| `offers` | `HasMany` | `OrderOffer` |
| `histories` | `HasMany` | `OrderStatusHistory` |
| `acceptedOffer` | `BelongsTo` | `OrderOffer` |
| `city` | `BelongsTo` | `City` |
| `region` | `BelongsTo` | `Region` |
| `orderSkills` | `HasMany` | `OrderSkill` |
| `skills` | `BelongsToMany` | `Skill` |
| `conversation` | `MorphOne` | `Conversation` |

## Traits
- `App\Traits\Reviewable`

## Enums Used
- `status` => `OrderStatusEnum::class`

## Notable Features
- Uses computed accessors for `totalFees`, `userTotal`, `providerTotal`
- Default status is `New`
- Has factory for testing (`HasFactory`)
- Uses UUIDs for primary key (`HasUuids`)
- Interacts with media library for attachments (`InteractsWithMedia`)

---

# Model: OrderOffer

**Table:** `order_offers`  
**File:** `app/Models/OrderOffer.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `order_id` | `Unknown` | foreign key candidate |
| `user_id` | `Unknown` | foreign key candidate |
| `provider_id` | `Unknown` | foreign key candidate |
| `category_id` | `Unknown` | foreign key candidate |
| `price` | `Unknown` | - |
| `description` | `Unknown` | - |
| `status` | `OfferStatusEnum::class` | cast |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `order` | `BelongsTo` | `Order` |
| `user` | `BelongsTo` | `User` |
| `provider` | `BelongsTo` | `Provider` |
| `histories` | `HasMany` | `OrderOfferHistory` |

## Traits
- None detected

## Enums Used
- `status` => `OfferStatusEnum::class`

---

# Model: OrderOfferHistory

**Table:** `order_offers_histories`  
**File:** `app/Models/OrderOfferHistory.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `order_id` | `Unknown` | foreign key candidate |
| `order_offer_id` | `Unknown` | foreign key candidate |
| `price` | `Unknown` | - |
| `description` | `Unknown` | - |
| `status` | `OfferStatusEnum::class` | cast |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `order` | `BelongsTo` | `Order` |
| `orderOffer` | `BelongsTo` | `OrderOffer` |

## Traits
- None detected

## Enums Used
- `status` => `OfferStatusEnum::class`

---

# Model: OrderSkill

**Table:** `order_skills`  
**File:** `app/Models/OrderSkill.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| (none detected from fillable) | Unknown | Check migrations/model attributes |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: OrderStatusHistory

**Table:** `order_status_historys`  
**File:** `app/Models/OrderStatusHistory.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `order_id` | `Unknown` | foreign key candidate |
| `status` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `order` | `BelongsTo` | `Order` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Page

**Table:** `pages`  
**File:** `app/Models/Page.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `slug` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: PageTranslation

**Table:** `page_translations`  
**File:** `app/Models/PageTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `Unknown` | - |
| `content` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `page` | `BelongsTo` | `Page` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Payment

**Table:** `payments`  
**File:** `app/Models/Payment.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `transaction_id` | `Unknown` | foreign key candidate |
| `driver` | `Unknown` | - |
| `request` | `Unknown` | - |
| `response` | `Unknown` | - |
| `amount` | `Unknown` | - |
| `status` | `Unknown` | - |
| `message` | `Unknown` | - |
| `url` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |
| `product_id` | `Unknown` | foreign key candidate |
| `product_type` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `MorphTo` | `Unknown` |
| `product` | `MorphTo` | `Unknown` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: PropertiyCategory

**Table:** `propertiy_categorys`  
**File:** `app/Models/PropertiyCategory.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `parent_id` | `'integer'` | cast, foreign key candidate |
| `is_active` | `'boolean'` | cast |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: PropertiyCategoryTranslation

**Table:** `propertiy_category_translations`  
**File:** `app/Models/PropertiyCategoryTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `'string'` | cast |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `propertiyCategory` | `BelongsTo` | `PropertiyCategory` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: PropertyAdvisement

**Table:** `property_advisements`  
**File:** `app/Models/PropertyAdvisement.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `description` | `Unknown` | - |
| `normalized_description` | `Unknown` | - |
| `image` | `Unknown` | - |
| `status` | `Unknown` | - |
| `operation` | `Unknown` | - |
| `facade` | `Unknown` | - |
| `street_width` | `Unknown` | - |
| `street_type` | `Unknown` | - |
| `user_type` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `age` | `Unknown` | - |
| `area` | `Unknown` | - |
| `price` | `Unknown` | - |
| `bedrooms_count` | `Unknown` | - |
| `bathrooms_count` | `Unknown` | - |
| `halls_count` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `license` | `Unknown` | - |
| `options` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `address` | `Unknown` | - |
| `property_type_id` | `Unknown` | foreign key candidate |
| `city_id` | `Unknown` | foreign key candidate |
| `region_id` | `Unknown` | foreign key candidate |
| `category_id` | `Unknown` | foreign key candidate |
| `show_price` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `category` | `BelongsTo` | `PropertiyCategory` |
| `propertyType` | `BelongsTo` | `PropertyType` |
| `city` | `BelongsTo` | `City` |
| `region` | `BelongsTo` | `Region` |
| `user` | `MorphTo` | `Unknown` |

## Traits
- `App\Traits\HasNormalizedAttributes`

## Enums Used
- None detected in casts()

---

# Model: PropertyType

**Table:** `property_types`  
**File:** `app/Models/PropertyType.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `is_active` | `'boolean'` | cast |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: PropertyTypeTranslation

**Table:** `property_type_translations`  
**File:** `app/Models/PropertyTypeTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `name` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `propertyType` | `BelongsTo` | `PropertyType` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Provider

**Table:** `providers`  
**File:** `app/Models/Provider.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `name` | `Unknown` | - |
| `code` | `Unknown` | - |
| `iban` | `Unknown` | - |
| `about` | `Unknown` | - |
| `logo` | `Unknown` | - |
| `tax_number` | `Unknown` | - |
| `phone` | `Unknown` | - |
| `email` | `Unknown` | - |
| `website` | `Unknown` | - |
| `address` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `provider_type_id` | `Unknown` | foreign key candidate |
| `region_id` | `Unknown` | foreign key candidate |
| `city_id` | `Unknown` | foreign key candidate |
| `status` | `ProviderStatusEnum::class` | cast |
| `password` | `'hashed'` | cast |
| `language` | `Unknown` | - |
| `blocked_at` | `'datetime'` | cast |
| `blocked_until` | `'datetime'` | cast |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `providerType` | `BelongsTo` | `ProviderType` |
| `city` | `BelongsTo` | `City` |
| `region` | `BelongsTo` | `Region` |
| `categorySkills` | `HasMany` | `CategorySkill` |
| `skills` | `BelongsToMany` | `Skill` |
| `providerCategories` | `HasMany` | `ProviderCategory` |
| `categories` | `BelongsToMany` | `Category` |
| `orders` | `HasMany` | `Order` |
| `orderOffers` | `HasMany` | `OrderOffer` |

## Traits
- `App\Traits\Blockable`
- `App\Traits\HasBroadcastChanel`
- `App\Traits\HasJobs`
- `App\Traits\HasPayments`
- `App\Traits\HasReviews`
- `App\Traits\HasRoles`
- `App\Traits\HasWallet`
- `Spatie\MediaLibrary\InteractsWithMedia`
- `Illuminate\Notifications\Notifiable`

## Enums Used
- `status` => `ProviderStatusEnum::class`

## Notable Features
- Computes `logoUrl`, `commercialRecordUrl`, `paddedCode` accessors
- Used by: provider dashboard session guard, Sanctum API auth
- Relationship to categories and skills allows querying providers by their capabilities

---

# Model: ProviderCategory

**Table:** `provider_categorys`  
**File:** `app/Models/ProviderCategory.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `category_id` | `Unknown` | foreign key candidate |
| `provider_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `category` | `BelongsTo` | `Category` |
| `provider` | `BelongsTo` | `Provider` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: ProviderType

**Table:** `provider_types`  
**File:** `app/Models/ProviderType.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `files` | `'array'` | cast |
| `image` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `providers` | `HasMany` | `Provider` |
| `categories` | `BelongsToMany` | `Unknown` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: ProviderTypeTranslation

**Table:** `provider_type_translations`  
**File:** `app/Models/ProviderTypeTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `name` | `Unknown` | - |
| `provider_type_id` | `Unknown` | foreign key candidate |
| `locale` | `Unknown` | - |
| `description` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `providerType` | `BelongsTo` | `ProviderType` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Question

**Table:** `questions`  
**File:** `app/Models/Question.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| (none detected from fillable) | Unknown | Check migrations/model attributes |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: QuestionTranslation

**Table:** `question_translations`  
**File:** `app/Models/QuestionTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `Unknown` | - |
| `answer` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `question` | `BelongsTo` | `Question` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Region

**Table:** `regions`  
**File:** `app/Models/Region.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| (none detected from fillable) | Unknown | Check migrations/model attributes |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `cities` | `HasMany` | `City` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: RegionTranslation

**Table:** `region_translations`  
**File:** `app/Models/RegionTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `locale` | `Unknown` | - |
| `region_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `region` | `BelongsTo` | `Region` |

## Traits
- `App\Traits\HasNormalizedAttributes`

## Enums Used
- None detected in casts()

---

# Model: RegisterVerificationCode

**Table:** `register_verification_codes`  
**File:** `app/Models/RegisterVerificationCode.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `token` | `Unknown` | - |
| `queryable` | `Unknown` | - |
| `expires_at` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Review

**Table:** `reviews`  
**File:** `app/Models/Review.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `reviewer_type` | `Unknown` | - |
| `reviewer_id` | `Unknown` | foreign key candidate |
| `reviewee_type` | `Unknown` | - |
| `reviewee_id` | `Unknown` | foreign key candidate |
| `operation_type` | `Unknown` | - |
| `operation_id` | `Unknown` | foreign key candidate |
| `rating` | `Unknown` | - |
| `comment` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `reviewer` | `MorphTo` | `Unknown` |
| `reviewee` | `MorphTo` | `Unknown` |
| `operation` | `MorphTo` | `Unknown` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Setting

**Table:** `settings`  
**File:** `app/Models/Setting.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `key` | `Unknown` | - |
| `content` | `Unknown` | - |
| `group` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Skill

**Table:** `skills`  
**File:** `app/Models/Skill.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `category_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `category` | `BelongsTo` | `Category` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: SkillTranslation

**Table:** `skill_translations`  
**File:** `app/Models/SkillTranslation.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `title` | `Unknown` | - |
| `normalized_title` | `Unknown` | - |
| `locale` | `Unknown` | - |
| `skill_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `skill` | `BelongsTo` | `Skill` |

## Traits
- `App\Traits\HasNormalizedAttributes`

## Enums Used
- None detected in casts()

---

# Model: System

**Table:** `systems`  
**File:** `app/Models/System.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `name` | `Unknown` | - |
| `online` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| (none) | - | - |

## Traits
- `App\Traits\HasBroadcastChanel`

## Enums Used
- None detected in casts()

---

# Model: TicketSupport

**Table:** `ticket_supports`  
**File:** `app/Models/TicketSupport.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `user_type` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `operation_type` | `Unknown` | - |
| `operation_id` | `Unknown` | foreign key candidate |
| `title` | `Unknown` | - |
| `message` | `Unknown` | - |
| `status` | `TicketSupportStatusEnum::class` | cast |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `MorphTo` | `Unknown` |
| `operation` | `MorphTo` | `Unknown` |
| `chat` | `MorphOne` | `Unknown` |

## Traits
- None detected

## Enums Used
- `status` => `TicketSupportStatusEnum::class`

---

# Model: TopUpRequest

**Table:** `top_up_requests`  
**File:** `app/Models/TopUpRequest.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |
| `amount` | `Unknown` | - |
| `status` | `OperationStatusEnum::class` | cast |
| `wallet_id` | `Unknown` | foreign key candidate |
| `payment_method` | `PaymentMethodEnum::class` | cast |
| `transaction_id` | `Unknown` | foreign key candidate |
| `payment_status` | `PaymentStatusEnum::class` | cast |
| `admin_notes` | `Unknown` | - |
| `transaction_image` | `Unknown` | - |
| `user_notes` | `Unknown` | - |
| `admin_id` | `Unknown` | foreign key candidate |
| `payment_driver` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `MorphTo` | `Wallet` |
| `wallet` | `BelongsTo` | `Unknown` |
| `admin` | `BelongsTo` | `Admin` |
| `payment` | `MorphOne` | `Payment` |

## Traits
- None detected

## Enums Used
- `payment_method` => `PaymentMethodEnum::class`
- `payment_status` => `PaymentStatusEnum::class`
- `status` => `OperationStatusEnum::class`

---

# Model: User

**Table:** `users`  
**File:** `app/Models/User.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `f_name` | `Unknown` | - |
| `l_name` | `Unknown` | - |
| `image` | `Unknown` | - |
| `email` | `Unknown` | - |
| `language` | `Unknown` | - |
| `latitude` | `Unknown` | - |
| `longitude` | `Unknown` | - |
| `password` | `'hashed'` | cast |
| `phone` | `Unknown` | - |
| `nationality_id` | `Unknown` | foreign key candidate |
| `status` | `UserStatusEnum::class` | cast |
| `blocked_at` | `'datetime'` | cast |
| `blocked_until` | `'datetime'` | cast |
| `player_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `nationality` | `BelongsTo` | `Nationality` |
| `orders` | `HasMany` | `Order` |
| `propertyAdvisements` | `MorphMany` | `PropertyAdvisement` |
| `carAdvisements` | `MorphMany` | `CarAdvisement` |
| `receivedMessages` | `MorphMany` | `ConversationMessage` |
| `unreadReceivedMessages` | `MorphMany` | `ConversationMessage` |
| `sentMessages` | `MorphMany` | `ConversationMessage` |
| `unreadSentMessages` | `MorphMany` | `ConversationMessage` |

## Traits
- `App\Traits\Blockable`
- `App\Traits\HasBroadcastChanel`
- `App\Traits\HasJobs`
- `App\Traits\HasOTPs`
- `App\Traits\HasPayments`
- `App\Traits\HasWallet`
- `Illuminate\Foundation\Auth\User` (via `HasApiTokens`, `Notifiable`)

## Enums Used
- `status` => `UserStatusEnum::class`

## Notable Features
- Computes `name` and `image_url` accessors
- `markLoginAsVerified()` from `HasOTPs` trait issues a Sanctum token
- Used by: API auth guard `user-api` (Sanctum-protected)

---

# Model: VerificationCode

**Table:** `verification_codes`  
**File:** `app/Models/VerificationCode.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `type` | `Unknown` | - |
| `token` | `Unknown` | - |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |
| `expire_at` | `Unknown` | - |
| `expiration_activated` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `MorphTo` | `Unknown` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: Wallet

**Table:** `wallets`  
**File:** `app/Models/Wallet.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |
| `balance` | `Unknown` | - |
| `pending_credit` | `Unknown` | - |
| `pending_debit` | `Unknown` | - |
| `total_earning` | `Unknown` | - |
| `total_spent` | `Unknown` | - |
| `debit` | `Unknown` | - |
| `credit` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `transactions` | `HasMany` | `WalletTransaction` |
| `user` | `MorphTo` | `Unknown` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: WalletTransaction

**Table:** `wallet_transactions`  
**File:** `app/Models/WalletTransaction.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `wallet_id` | `Unknown` | foreign key candidate |
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |
| `credit` | `Unknown` | - |
| `debit` | `Unknown` | - |
| `balance_before` | `Unknown` | - |
| `balance_after` | `Unknown` | - |
| `description` | `Unknown` | - |
| `operation_id` | `Unknown` | foreign key candidate |
| `operation_type` | `Unknown` | - |
| `pending_credit` | `Unknown` | - |
| `pending_debit` | `Unknown` | - |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `operation` | `MorphTo` | `Wallet` |
| `wallet` | `BelongsTo` | `Unknown` |
| `user` | `MorphTo` | `Unknown` |

## Traits
- None detected

## Enums Used
- None detected in casts()

---

# Model: WithdrawRequest

**Table:** `withdraw_requests`  
**File:** `app/Models/WithdrawRequest.php`

## Fields
| Field | Type | Notes |
|---|---|---|
| `user_id` | `Unknown` | foreign key candidate |
| `user_type` | `Unknown` | - |
| `amount` | `Unknown` | - |
| `status` | `OperationStatusEnum::class` | cast |
| `wallet_id` | `Unknown` | foreign key candidate |
| `admin_notes` | `Unknown` | - |
| `user_notes` | `Unknown` | - |
| `admin_id` | `Unknown` | foreign key candidate |

## Relationships
| Method | Type | Related Model |
|---|---|---|
| `user` | `MorphTo` | `Wallet` |
| `wallet` | `BelongsTo` | `Unknown` |
| `admin` | `BelongsTo` | `Admin` |

## Traits
- None detected

## Enums Used
- `status` => `OperationStatusEnum::class`

---

