# API Inventory

Regenerated from `php artisan route:list --json` (ground truth for method, URI, name, middleware, controller) plus controller reflection for FormRequests and Resources.

**Last verified: 2026-07-26, post-full-module-extraction**

## Scope

| Module | Endpoints |
|---|---:|
| App Core | 22 |
| Catalog | 14 |
| Chat | 12 |
| Classifieds | 28 |
| Cms | 5 |
| Guarantor | 15 |
| Jobs | 7 |
| Marketplace | 5 |
| Opportunity | 19 |
| Orders | 9 |
| Payment | 3 |
| Support | 6 |
| Wallet | 4 |
| **Total `api/*` routes** | **149** |

## Reading this document

- **Auth** comes from route middleware: `auth:user-api + abilities:user-api` = mobile-user-exclusive; `auth:sanctum` = any Sanctum token (user or provider); `No` = public.
- **Audience**: `Mobile (user-api)` routes are the mobile-app-exclusive user API (`Api\V1\User\*` + Orders user routes). `Sanctum` routes serve both apps. `Public` routes are unauthenticated catalog/content lookups.
- **Request** is the FormRequest type-hinted on the controller action (`none` = no FormRequest; inline validation or none).
- **Resources** are Resource/Collection classes referenced in the action body; `(HasApiResponse helper / raw JSON)` means the `MMAE\ApiResponse` envelope (`success`, `data`, `message`, `errors`, sometimes `token`) with an ad-hoc payload.
- All module API responses ride the same `HasApiResponse` envelope unless stated otherwise.
- **Out of scope**: Dashboard (Inertia, `dashboard/*`) and Provider web routes are not `api/*` routes and are intentionally not listed here.

## Contract-freeze cross-reference (authoritative shapes)

These Pest contract tests lock actual response shapes and take precedence over shapes re-derived from controller code:

| Area | Test | Locked shape highlights |
|---|---|---|
| Geo catalog | `Modules/Geo/tests/Feature/Api/V1/CatalogGeoApiContractTest.php` | region/city items: `id`, `title`, `translations` (city has **no** `cities_count`); nationality: `id`, `name`, `translations` |
| Marketplace catalog | `Modules/Marketplace/tests/Feature/Api/V1/MarketplaceCatalogContractTest.php` | categories/skills/provider-types list shapes |
| Property catalog | `Modules/Catalog/tests/Feature/PropertyCatalogResponseContractTest.php` | property-categories / property-types lookups |
| Cms | `tests/Feature/Api/V1/CmsResponseContractTest.php` | banners / pages / questions |
| Jobs | `tests/Feature/Api/V1/JobResponseContractTest.php` | job item: city `id`,`region_id`,`title`; region `id`,`title`; nationality `id`,`name` |
| Orders (user) | `tests/Feature/Api/V1/User/OrderControllerTest.php` + `OrderControllerRegressionTest.php` | order city/region: `id`, `title` |
| UserResource (mobile) | `tests/Feature/Api/V1/UserResourceMobileContractTest.php` | register/me/profile-update user payload |
| UserResource (nested) | `tests/Feature/Api/V1/UserResourceNonMobileConsumersContractTest.php` | Wallet + Classifieds nested `user` |
| OTP phone verify | `tests/Feature/Api/V1/OtpVerifyPhoneResponseTest.php` | envelope stays `success: false` (deferred Item 4) |

## Known deferred quirks (do NOT re-fix without mobile coordination)

See `docs/DEFERRED_MOBILE_BREAKING_CHANGES.md`:

1. Chat conversations emit deprecated typo key `last_massage_at` alongside `last_message_at`.
2. **Pagination shape fragmentation**: flat meta (`BaseCollection`: `items` + top-level `total`/`count`/…) vs nested `paginate` (Chat `ConversationCollection`) vs nested `paginate` + page URLs (`ConversationMessageCollection`). Documented per-family, intentionally not unified on v1.
3. Wallet `add-balance` leaks `PaymentInitResult` internals (`status`, `driver`, `url`, `payable`, `transaction_id`, `message` + nested `data`).
4. `POST /api/v1/otp/verify` with `type=phone` persists verification but still returns `success: false`.
5. `propertiy_categories` schema typo persists behind correctly-named `property-categories` endpoints.

---

# App Core

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| GET/POST | `/api/broadcasting/auth` | - | `Illuminate\Broadcasting\BroadcastController@authenticate` | auth:sanctum | Sanctum (user or provider) | none | none detected |
| GET | `/api/v1/auth/counts` | - | `App\Http\Controllers\Api\UserController@counts` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/auth/delete-account` | - | `App\Http\Controllers\Api\UserController@deleteAccount` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| DELETE | `/api/v1/auth/notifications/all` | - | `App\Http\Controllers\Api\UserController@deleteAllNotification` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/auth/notifications/mark-all-as-read` | - | `App\Http\Controllers\Api\UserController@markAllNotificationsAsRead` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/auth/notifications/{notification}/mark-as-read` | - | `App\Http\Controllers\Api\UserController@markAsRead` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| DELETE | `/api/v1/auth/notifications/{notification}` | - | `App\Http\Controllers\Api\UserController@deleteNotification` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/auth/notifications` | - | `App\Http\Controllers\Api\UserController@notifications` | auth:sanctum | Sanctum (user or provider) | none | `NotificationCollection` |
| POST | `/api/v1/auth/update-settings` | - | `App\Http\Controllers\Api\UserController@updateSettings` | auth:sanctum | Sanctum (user or provider) | `UpdateSettingsRequest` | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/catalog/nationalities` | - | `App\Http\Controllers\Api\V1\CatalogController@nationalities` | No | Public | none | `NationalityCollection` |
| GET | `/api/v1/catalog/providers` | - | `App\Http\Controllers\Api\V1\CatalogController@providers` | No | Public | none | `ProviderResource` |
| GET | `/api/v1/catalog/regions/{region}/cities` | - | `App\Http\Controllers\Api\V1\CatalogController@cities` | No | Public | none | `CityCollection` |
| GET | `/api/v1/catalog/regions` | - | `App\Http\Controllers\Api\V1\CatalogController@regions` | No | Public | none | `RegionCollection` |
| GET | `/api/v1/catalog/settings` | - | `App\Http\Controllers\Api\V1\CatalogController@settings` | No | Public | none | (HasApiResponse helper / raw JSON) |
| POST | `/api/v1/otp/send` | - | `App\Http\Controllers\Api\V1\OtpController@send` | auth:sanctum | Sanctum (user or provider) | `SendOTPRequest` | (HasApiResponse helper / raw JSON) |
| POST | `/api/v1/otp/verify` | - | `App\Http\Controllers\Api\V1\OtpController@verify` | auth:sanctum | Sanctum (user or provider) | `VerifyOTPRequest` | (HasApiResponse helper / raw JSON) |
| POST | `/api/v1/user/auth/login` | - | `App\Http\Controllers\Api\V1\User\AuthController@login` | No | Public | `LoginRequest` | (HasApiResponse helper / raw JSON) |
| POST | `/api/v1/user/auth/logout` | - | `App\Http\Controllers\Api\V1\User\AuthController@logout` | auth:user-api + abilities:user-api | Mobile (user-api) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/user/auth/me` | - | `App\Http\Controllers\Api\V1\User\AuthController@auth` | auth:user-api + abilities:user-api | Mobile (user-api) | none | `UserResource` |
| POST | `/api/v1/user/auth/profile/update` | - | `App\Http\Controllers\Api\V1\User\AuthController@profileUpdate` | auth:user-api + abilities:user-api | Mobile (user-api) | `UpdateRequest` | `UserResource` |
| POST | `/api/v1/user/auth/register` | - | `App\Http\Controllers\Api\V1\User\AuthController@register` | No | Public | `RegisterRequest` | `UserResource` |
| GET | `/api/v1/user/providers/get` | - | `App\Http\Controllers\Api\V1\User\ProviderController@get` | auth:user-api + abilities:user-api | Mobile (user-api) | `FindProviderRequest` | `ProviderResource` |

# Catalog

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| GET | `/api/v1/catalog/car-brands/{carBrand}` | - | `Modules\Catalog\Http\Controllers\Api\V1\CarBrandController@show` | No | Public | none | `CarBrandResource` |
| GET | `/api/v1/catalog/car-brands` | - | `Modules\Catalog\Http\Controllers\Api\V1\CarBrandController@index` | No | Public | none | `CarBrandCollection` |
| GET | `/api/v1/catalog/car-categories/{carCategory}` | - | `Modules\Catalog\Http\Controllers\Api\V1\CarCategoryController@show` | No | Public | none | `CarCategoryResource` |
| GET | `/api/v1/catalog/car-categories` | - | `Modules\Catalog\Http\Controllers\Api\V1\CarCategoryController@index` | No | Public | none | `CarCategoryCollection` |
| GET | `/api/v1/catalog/car-types/{carType}` | - | `Modules\Catalog\Http\Controllers\Api\V1\CarTypeController@show` | No | Public | none | `CarTypeResource` |
| GET | `/api/v1/catalog/car-types` | - | `Modules\Catalog\Http\Controllers\Api\V1\CarTypeController@index` | No | Public | none | `CarTypeCollection` |
| GET | `/api/v1/catalog/device-categories/{deviceCategory}` | - | `Modules\Catalog\Http\Controllers\Api\V1\DeviceCategoryController@show` | No | Public | none | `DeviceCategoryResource` |
| GET | `/api/v1/catalog/device-categories` | - | `Modules\Catalog\Http\Controllers\Api\V1\DeviceCategoryController@index` | No | Public | none | `DeviceCategoryResource` |
| GET | `/api/v1/catalog/electronic-brands/{electronicBrand}` | - | `Modules\Catalog\Http\Controllers\Api\V1\ElectronicBrandController@show` | No | Public | none | `ElectronicBrandResource` |
| GET | `/api/v1/catalog/electronic-brands` | - | `Modules\Catalog\Http\Controllers\Api\V1\ElectronicBrandController@index` | No | Public | none | `ElectronicBrandResource` |
| GET | `/api/v1/catalog/property-categories` | - | `Modules\Catalog\Http\Controllers\Api\V1\PropertyCategoryController@index` | No | Public | none | `PropertyCategoryCollection` |
| GET | `/api/v1/catalog/property-types` | - | `Modules\Catalog\Http\Controllers\Api\V1\PropertyTypeController@index` | No | Public | none | `PropertyTypeCollection` |
| GET | `/api/v1/catalog/specializations/{specialization}` | - | `Modules\Catalog\Http\Controllers\Api\V1\SpecializationController@show` | No | Public | none | `SpecializationResource` |
| GET | `/api/v1/catalog/specializations` | - | `Modules\Catalog\Http\Controllers\Api\V1\SpecializationController@index` | No | Public | none | `SpecializationResource` |

# Chat

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| POST | `/api/v1/chats/orders/send/{conversation}` | - | `Modules\Chat\Http\Controllers\Api\V1\OrderChatController@send` | auth:sanctum | Sanctum (user or provider) | `SendMessageRequest` | `ConversationMessageResource` |
| GET | `/api/v1/chats/orders/{conversation}` | - | `Modules\Chat\Http\Controllers\Api\V1\OrderChatController@show` | auth:sanctum | Sanctum (user or provider) | none | `ConversationMessageCollection` |
| GET | `/api/v1/chats/orders` | - | `Modules\Chat\Http\Controllers\Api\V1\OrderChatController@index` | auth:sanctum | Sanctum (user or provider) | none | `ConversationCollection` |
| POST | `/api/v1/chats/orders` | - | `Modules\Chat\Http\Controllers\Api\V1\OrderChatController@store` | auth:sanctum | Sanctum (user or provider) | `StoreOrderChatRequest` | `ConversationResource` |
| POST | `/api/v1/chats/send/{conversation}` | - | `Modules\Chat\Http\Controllers\Api\V1\MemberChatController@send` | auth:sanctum | Sanctum (user or provider) | `SendMessageRequest` | `ConversationMessageResource` |
| POST | `/api/v1/chats/tickets/send/{conversation}` | - | `Modules\Chat\Http\Controllers\Api\V1\TicketSupportChatController@send` | auth:sanctum | Sanctum (user or provider) | `SendMessageRequest` | `ConversationMessageResource` |
| GET | `/api/v1/chats/tickets/{conversation}` | - | `Modules\Chat\Http\Controllers\Api\V1\TicketSupportChatController@show` | auth:sanctum | Sanctum (user or provider) | none | `ConversationMessageCollection` |
| GET | `/api/v1/chats/tickets` | - | `Modules\Chat\Http\Controllers\Api\V1\TicketSupportChatController@index` | auth:sanctum | Sanctum (user or provider) | none | `ConversationCollection` |
| GET | `/api/v1/chats/{conversation}/show` | - | `Modules\Chat\Http\Controllers\Api\V1\MemberChatController@chat` | auth:sanctum | Sanctum (user or provider) | none | `ConversationResource` |
| GET | `/api/v1/chats/{conversation}` | - | `Modules\Chat\Http\Controllers\Api\V1\MemberChatController@show` | auth:sanctum | Sanctum (user or provider) | none | `ConversationMessageCollection` |
| GET | `/api/v1/chats` | - | `Modules\Chat\Http\Controllers\Api\V1\MemberChatController@index` | auth:sanctum | Sanctum (user or provider) | none | `ConversationCollection` |
| POST | `/api/v1/chats` | - | `Modules\Chat\Http\Controllers\Api\V1\MemberChatController@store` | auth:sanctum | Sanctum (user or provider) | `StoreConversationRequest` | `ConversationResource` |

# Classifieds

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| GET | `/api/v1/classifieds/cars/all` | `api.v1.classifieds.cars.all` | `Modules\Classifieds\Http\Controllers\Api\V1\CarAdvisementController@all` | No | Public | none | `CarAdvisementCollection` |
| DELETE | `/api/v1/classifieds/cars/{carAdvisement}/media/{media:uuid}` | `api.v1.classifieds.cars.deleteMedia` | `Modules\Classifieds\Http\Controllers\Api\V1\CarAdvisementController@deleteMedia` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| DELETE | `/api/v1/classifieds/cars/{carAdvisement}` | `api.v1.classifieds.cars.destroy` | `Modules\Classifieds\Http\Controllers\Api\V1\CarAdvisementController@destroy` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/classifieds/cars/{carAdvisement}` | `api.v1.classifieds.cars.show` | `Modules\Classifieds\Http\Controllers\Api\V1\CarAdvisementController@show` | auth:sanctum | Sanctum (user or provider) | none | `CarAdvisementResource` |
| PUT/PATCH | `/api/v1/classifieds/cars/{carAdvisement}` | `api.v1.classifieds.cars.update` | `Modules\Classifieds\Http\Controllers\Api\V1\CarAdvisementController@update` | auth:sanctum | Sanctum (user or provider) | `CarAdvisementRequest` | `CarAdvisementResource` |
| GET | `/api/v1/classifieds/cars` | `api.v1.classifieds.cars.index` | `Modules\Classifieds\Http\Controllers\Api\V1\CarAdvisementController@index` | auth:sanctum | Sanctum (user or provider) | none | `CarAdvisementCollection` |
| POST | `/api/v1/classifieds/cars` | `api.v1.classifieds.cars.store` | `Modules\Classifieds\Http\Controllers\Api\V1\CarAdvisementController@store` | auth:sanctum | Sanctum (user or provider) | `CarAdvisementRequest` | `CarAdvisementResource` |
| GET | `/api/v1/classifieds/electronics/all` | `api.v1.classifieds.electronics.all` | `Modules\Classifieds\Http\Controllers\Api\V1\ElectronicAdvisementController@all` | No | Public | none | `ElectronicAdvisementCollection` |
| DELETE | `/api/v1/classifieds/electronics/{electronicAdvisement}/media/{media:uuid}` | `api.v1.classifieds.electronics.deleteMedia` | `Modules\Classifieds\Http\Controllers\Api\V1\ElectronicAdvisementController@deleteMedia` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| DELETE | `/api/v1/classifieds/electronics/{electronicAdvisement}` | `api.v1.classifieds.electronics.destroy` | `Modules\Classifieds\Http\Controllers\Api\V1\ElectronicAdvisementController@destroy` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/classifieds/electronics/{electronicAdvisement}` | `api.v1.classifieds.electronics.show` | `Modules\Classifieds\Http\Controllers\Api\V1\ElectronicAdvisementController@show` | auth:sanctum | Sanctum (user or provider) | none | `ElectronicAdvisementResource` |
| PUT/PATCH | `/api/v1/classifieds/electronics/{electronicAdvisement}` | `api.v1.classifieds.electronics.update` | `Modules\Classifieds\Http\Controllers\Api\V1\ElectronicAdvisementController@update` | auth:sanctum | Sanctum (user or provider) | `ElectronicAdvisementRequest` | `ElectronicAdvisementResource` |
| GET | `/api/v1/classifieds/electronics` | `api.v1.classifieds.electronics.index` | `Modules\Classifieds\Http\Controllers\Api\V1\ElectronicAdvisementController@index` | auth:sanctum | Sanctum (user or provider) | none | `ElectronicAdvisementCollection` |
| POST | `/api/v1/classifieds/electronics` | `api.v1.classifieds.electronics.store` | `Modules\Classifieds\Http\Controllers\Api\V1\ElectronicAdvisementController@store` | auth:sanctum | Sanctum (user or provider) | `ElectronicAdvisementRequest` | `ElectronicAdvisementResource` |
| GET | `/api/v1/classifieds/institutes/all` | `api.v1.classifieds.institutes.all` | `Modules\Classifieds\Http\Controllers\Api\V1\InstituteAdvisementController@all` | No | Public | none | `InstituteAdvisementCollection` |
| DELETE | `/api/v1/classifieds/institutes/{instituteAdvisement}/media/{media:uuid}` | `api.v1.classifieds.institutes.deleteMedia` | `Modules\Classifieds\Http\Controllers\Api\V1\InstituteAdvisementController@deleteMedia` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| DELETE | `/api/v1/classifieds/institutes/{instituteAdvisement}` | `api.v1.classifieds.institutes.destroy` | `Modules\Classifieds\Http\Controllers\Api\V1\InstituteAdvisementController@destroy` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/classifieds/institutes/{instituteAdvisement}` | `api.v1.classifieds.institutes.show` | `Modules\Classifieds\Http\Controllers\Api\V1\InstituteAdvisementController@show` | auth:sanctum | Sanctum (user or provider) | none | `InstituteAdvisementResource` |
| PUT/PATCH | `/api/v1/classifieds/institutes/{instituteAdvisement}` | `api.v1.classifieds.institutes.update` | `Modules\Classifieds\Http\Controllers\Api\V1\InstituteAdvisementController@update` | auth:sanctum | Sanctum (user or provider) | `InstituteAdvisementRequest` | `InstituteAdvisementResource` |
| GET | `/api/v1/classifieds/institutes` | `api.v1.classifieds.institutes.index` | `Modules\Classifieds\Http\Controllers\Api\V1\InstituteAdvisementController@index` | auth:sanctum | Sanctum (user or provider) | none | `InstituteAdvisementCollection` |
| POST | `/api/v1/classifieds/institutes` | `api.v1.classifieds.institutes.store` | `Modules\Classifieds\Http\Controllers\Api\V1\InstituteAdvisementController@store` | auth:sanctum | Sanctum (user or provider) | `InstituteAdvisementRequest` | `InstituteAdvisementResource` |
| GET | `/api/v1/classifieds/properties/all` | `api.v1.classifieds.properties.all` | `Modules\Classifieds\Http\Controllers\Api\V1\PropertyAdvisementController@all` | No | Public | none | `PropertyAdvisementCollection` |
| DELETE | `/api/v1/classifieds/properties/{propertyAdvisement}/media/{media:uuid}` | `api.v1.classifieds.properties.deleteMedia` | `Modules\Classifieds\Http\Controllers\Api\V1\PropertyAdvisementController@deleteMedia` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| DELETE | `/api/v1/classifieds/properties/{propertyAdvisement}` | `api.v1.classifieds.properties.destroy` | `Modules\Classifieds\Http\Controllers\Api\V1\PropertyAdvisementController@destroy` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/classifieds/properties/{propertyAdvisement}` | `api.v1.classifieds.properties.show` | `Modules\Classifieds\Http\Controllers\Api\V1\PropertyAdvisementController@show` | auth:sanctum | Sanctum (user or provider) | none | `PropertyAdvisementResource` |
| PUT/PATCH | `/api/v1/classifieds/properties/{propertyAdvisement}` | `api.v1.classifieds.properties.update` | `Modules\Classifieds\Http\Controllers\Api\V1\PropertyAdvisementController@update` | auth:sanctum | Sanctum (user or provider) | `PropertyAdvisementRequest` | `PropertyAdvisementResource` |
| GET | `/api/v1/classifieds/properties` | `api.v1.classifieds.properties.index` | `Modules\Classifieds\Http\Controllers\Api\V1\PropertyAdvisementController@index` | auth:sanctum | Sanctum (user or provider) | none | `PropertyAdvisementCollection` |
| POST | `/api/v1/classifieds/properties` | `api.v1.classifieds.properties.store` | `Modules\Classifieds\Http\Controllers\Api\V1\PropertyAdvisementController@store` | auth:sanctum | Sanctum (user or provider) | `PropertyAdvisementRequest` | `PropertyAdvisementResource` |

# Cms

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| GET | `/api/v1/catalog/banners` | - | `Modules\Cms\Http\Controllers\Api\V1\CmsController@banners` | No | Public | none | `BannerResource` |
| GET | `/api/v1/catalog/pages/{page}` | - | `Modules\Cms\Http\Controllers\Api\V1\CmsController@page` | No | Public | none | `PageResource` |
| GET | `/api/v1/catalog/pages` | - | `Modules\Cms\Http\Controllers\Api\V1\CmsController@pages` | No | Public | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/catalog/questions` | - | `Modules\Cms\Http\Controllers\Api\V1\CmsController@questions` | No | Public | none | `QuestionCollection` |
| POST | `/api/v1/messages` | - | `Modules\Cms\Http\Controllers\Api\V1\MessageController@store` | auth:sanctum | Sanctum (user or provider) | `MessagRequest` | (HasApiResponse helper / raw JSON) |

# Guarantor

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| POST | `/api/v1/chats/guarantor/{conversation}/send` | `api.v1.chats.guarantor.send` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorChatController@send` | auth:sanctum | Sanctum (user or provider) | `SendGuarantorMessageRequest` | `ConversationMessageResource` |
| GET | `/api/v1/chats/guarantor/{conversation}` | `api.v1.chats.guarantor.show` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorChatController@show` | auth:sanctum | Sanctum (user or provider) | none | `ConversationMessageCollection` |
| GET | `/api/v1/chats/guarantor` | `api.v1.chats.guarantor.index` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorChatController@index` | auth:sanctum | Sanctum (user or provider) | none | `GuarantorConversationCollection` |
| POST | `/api/v1/chats/guarantor` | `api.v1.chats.guarantor.store` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorChatController@store` | auth:sanctum | Sanctum (user or provider) | `StoreChatRequest` | `GuarantorConversationResource` |
| POST | `/api/v1/guarantor/company` | `api.v1.guarantor.guarantor.store.company` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorController@storeCompany` | auth:sanctum | Sanctum (user or provider) | `StoreCompanyGuarantorRequest` | `GuarantorResource` |
| POST | `/api/v1/guarantor/individual` | `api.v1.guarantor.guarantor.store.individual` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorController@storeIndividual` | auth:sanctum | Sanctum (user or provider) | `StoreIndividualGuarantorRequest` | `GuarantorResource` |
| POST | `/api/v1/guarantor/{guarantorRequest}/installments/{installment}/pay` | `api.v1.guarantor.guarantor.installments.pay` | `Modules\Guarantor\Http\Controllers\Api\V1\InstallmentController@pay` | auth:sanctum | Sanctum (user or provider) | `PayInstallmentRequest` | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/guarantor/{guarantorRequest}/installments` | `api.v1.guarantor.guarantor.installments.index` | `Modules\Guarantor\Http\Controllers\Api\V1\InstallmentController@index` | auth:sanctum | Sanctum (user or provider) | none | `InstallmentResource` |
| DELETE | `/api/v1/guarantor/{guarantorRequest}/media/{media:uuid}` | `api.v1.guarantor.guarantor.deleteMedia` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorController@deleteMedia` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| POST | `/api/v1/guarantor/{guarantorRequest}/pay` | `api.v1.guarantor.guarantor.pay` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorController@pay` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| POST | `/api/v1/guarantor/{guarantorRequest}/status` | `api.v1.guarantor.guarantor.updateStatus` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorController@updateStatus` | auth:sanctum | Sanctum (user or provider) | `UpdateGuarantorStatusRequest` | `GuarantorResource` |
| DELETE | `/api/v1/guarantor/{guarantorRequest}` | `api.v1.guarantor.guarantor.destroy` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorController@destroy` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/guarantor/{guarantorRequest}` | `api.v1.guarantor.guarantor.show` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorController@show` | auth:sanctum | Sanctum (user or provider) | none | `GuarantorResource` |
| POST | `/api/v1/guarantor/{guarantorRequest}` | `api.v1.guarantor.guarantor.update` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorController@update` | auth:sanctum | Sanctum (user or provider) | `UpdateGuarantorRequest` | `GuarantorResource` |
| GET | `/api/v1/guarantor` | `api.v1.guarantor.guarantor.index` | `Modules\Guarantor\Http\Controllers\Api\V1\GuarantorController@index` | auth:sanctum | Sanctum (user or provider) | none | `GuarantorCollection` |

# Jobs

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| GET | `/api/v1/jobs/all` | - | `Modules\Jobs\Http\Controllers\Api\V1\JobController@all` | auth:sanctum | Sanctum (user or provider) | none | `JobCollection` |
| DELETE | `/api/v1/jobs/{job}/media/{media}` | - | `Modules\Jobs\Http\Controllers\Api\V1\JobController@deleteMedia` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| DELETE | `/api/v1/jobs/{job}` | `jobs.destroy` | `Modules\Jobs\Http\Controllers\Api\V1\JobController@destroy` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/jobs/{job}` | - | `Modules\Jobs\Http\Controllers\Api\V1\JobController@show` | auth:sanctum | Sanctum (user or provider) | none | `JobResource` |
| PUT/PATCH | `/api/v1/jobs/{job}` | `jobs.update` | `Modules\Jobs\Http\Controllers\Api\V1\JobController@update` | auth:sanctum | Sanctum (user or provider) | `JobRequest` | `JobResource` |
| GET | `/api/v1/jobs` | `jobs.index` | `Modules\Jobs\Http\Controllers\Api\V1\JobController@index` | auth:sanctum | Sanctum (user or provider) | none | `JobCollection` |
| POST | `/api/v1/jobs` | `jobs.store` | `Modules\Jobs\Http\Controllers\Api\V1\JobController@store` | auth:sanctum | Sanctum (user or provider) | `JobRequest` | `JobResource` |

# Marketplace

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| GET | `/api/v1/catalog/categories/with-no-children` | - | `Modules\Marketplace\Http\Controllers\Api\V1\MarketplaceCatalogController@categoriesWithNoChildren` | No | Public | none | `CategoryCollection` |
| GET | `/api/v1/catalog/categories/{category}/children` | - | `Modules\Marketplace\Http\Controllers\Api\V1\MarketplaceCatalogController@categoryChildren` | No | Public | none | `CategoryCollection` |
| GET | `/api/v1/catalog/categories/{category}/skills` | - | `Modules\Marketplace\Http\Controllers\Api\V1\MarketplaceCatalogController@categorySkills` | No | Public | none | `SkillCollection` |
| GET | `/api/v1/catalog/categories` | - | `Modules\Marketplace\Http\Controllers\Api\V1\MarketplaceCatalogController@categories` | No | Public | none | `CategoryCollection` |
| GET | `/api/v1/catalog/provider-types` | - | `Modules\Marketplace\Http\Controllers\Api\V1\MarketplaceCatalogController@providerTypes` | No | Public | none | `ProviderTypeCollection` |

# Opportunity

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| POST | `/api/v1/chats/opportunities/{conversation}/send` | `api.v1.chats.opportunities.send` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityChatController@send` | auth:sanctum | Sanctum (user or provider) | `SendOpportunityChatMessageRequest` | `ConversationMessageResource` |
| GET | `/api/v1/chats/opportunities/{conversation}` | `api.v1.chats.opportunities.show` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityChatController@show` | auth:sanctum | Sanctum (user or provider) | none | `ConversationMessageCollection` |
| GET | `/api/v1/chats/opportunities` | `api.v1.chats.opportunities.index` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityChatController@index` | auth:sanctum | Sanctum (user or provider) | none | `OpportunityConversationCollection` |
| POST | `/api/v1/chats/opportunities` | `api.v1.chats.opportunities.store` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityChatController@store` | auth:sanctum | Sanctum (user or provider) | `StoreChatRequest` | `OpportunityConversationResource` |
| GET | `/api/v1/opportunities/all` | `api.v1.opportunity.opportunities.all` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController@all` | No | Public | none | `OpportunityCollection` |
| DELETE | `/api/v1/opportunities/{opportunity}/comments/{comment}` | `api.v1.opportunity.opportunities.comments.destroy` | `Modules\Opportunity\Http\Controllers\Api\V1\CommentController@destroy` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/opportunities/{opportunity}/comments` | `api.v1.opportunity.opportunities.comments.index` | `Modules\Opportunity\Http\Controllers\Api\V1\CommentController@index` | No | Public | none | `CommentCollection` |
| POST | `/api/v1/opportunities/{opportunity}/comments` | `api.v1.opportunity.opportunities.comments.store` | `Modules\Opportunity\Http\Controllers\Api\V1\CommentController@store` | auth:sanctum | Sanctum (user or provider) | `StoreCommentRequest` | `CommentResource` |
| DELETE | `/api/v1/opportunities/{opportunity}/media/{media:uuid}` | `api.v1.opportunity.opportunities.deleteMedia` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController@deleteMedia` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| POST | `/api/v1/opportunities/{opportunity}/offers/{offer}/accept` | `api.v1.opportunity.opportunities.offers.accept` | `Modules\Opportunity\Http\Controllers\Api\V1\OfferController@accept` | auth:sanctum | Sanctum (user or provider) | none | `OpportunityResource` |
| POST | `/api/v1/opportunities/{opportunity}/offers/{offer}/reject` | `api.v1.opportunity.opportunities.offers.reject` | `Modules\Opportunity\Http\Controllers\Api\V1\OfferController@reject` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/opportunities/{opportunity}/offers` | `api.v1.opportunity.opportunities.offers.index` | `Modules\Opportunity\Http\Controllers\Api\V1\OfferController@index` | auth:sanctum | Sanctum (user or provider) | none | `OfferCollection` |
| POST | `/api/v1/opportunities/{opportunity}/offers` | `api.v1.opportunity.opportunities.offers.store` | `Modules\Opportunity\Http\Controllers\Api\V1\OfferController@store` | auth:sanctum | Sanctum (user or provider) | `StoreOfferRequest` | `OfferResource` |
| POST | `/api/v1/opportunities/{opportunity}/renew` | `api.v1.opportunity.opportunities.renew` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController@renew` | auth:sanctum | Sanctum (user or provider) | `RenewOpportunityRequest` | `OpportunityResource` |
| DELETE | `/api/v1/opportunities/{opportunity}` | `api.v1.opportunity.opportunities.destroy` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController@destroy` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/opportunities/{opportunity}` | `api.v1.opportunity.opportunities.show` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController@show` | No | Public | none | `OpportunityResource` |
| POST | `/api/v1/opportunities/{opportunity}` | `api.v1.opportunity.opportunities.update` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController@update` | auth:sanctum | Sanctum (user or provider) | `UpdateOpportunityRequest` | `OpportunityResource` |
| GET | `/api/v1/opportunities` | `api.v1.opportunity.opportunities.index` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController@index` | auth:sanctum | Sanctum (user or provider) | none | `OpportunityCollection` |
| POST | `/api/v1/opportunities` | `api.v1.opportunity.opportunities.store` | `Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController@store` | auth:sanctum | Sanctum (user or provider) | `StoreOpportunityRequest` | `OpportunityResource` |

# Orders

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| POST | `/api/v1/user/orders/{order}/edit` | - | `Modules\Orders\Http\Controllers\Api\V1\OrderController@edit` | auth:user-api + abilities:user-api | Mobile (user-api) | `OrderRequest` | `OrderResource` |
| POST | `/api/v1/user/orders/{order}/end-and-review` | - | `Modules\Orders\Http\Controllers\Api\V1\OrderController@endAndReview` | auth:user-api + abilities:user-api | Mobile (user-api) | `EndAndReviewRequest` | (HasApiResponse helper / raw JSON) |
| DELETE | `/api/v1/user/orders/{order}/{media:uuid}/delete` | - | `Modules\Orders\Http\Controllers\Api\V1\OrderController@deleteMedia` | auth:user-api + abilities:user-api | Mobile (user-api) | none | (HasApiResponse helper / raw JSON) |
| POST | `/api/v1/user/orders/{order}/{offer}/pay` | - | `Modules\Orders\Http\Controllers\Api\V1\OrderController@pay` | auth:user-api + abilities:user-api | Mobile (user-api) | none | (HasApiResponse helper / raw JSON) |
| POST | `/api/v1/user/orders/{order}/{offer}/update-status` | - | `Modules\Orders\Http\Controllers\Api\V1\OrderController@updateOfferStatus` | auth:user-api + abilities:user-api | Mobile (user-api) | `UpdateOfferStatusRequest` | (HasApiResponse helper / raw JSON) |
| DELETE | `/api/v1/user/orders/{order}` | - | `Modules\Orders\Http\Controllers\Api\V1\OrderController@destroy` | auth:user-api + abilities:user-api | Mobile (user-api) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/user/orders/{order}` | - | `Modules\Orders\Http\Controllers\Api\V1\OrderController@show` | auth:user-api + abilities:user-api | Mobile (user-api) | none | `OrderResource` |
| GET | `/api/v1/user/orders` | - | `Modules\Orders\Http\Controllers\Api\V1\OrderController@index` | auth:user-api + abilities:user-api | Mobile (user-api) | none | `OrderCollection` |
| POST | `/api/v1/user/orders` | - | `Modules\Orders\Http\Controllers\Api\V1\OrderController@store` | auth:user-api + abilities:user-api | Mobile (user-api) | `OrderRequest` | `OrderResource` |

# Payment

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| POST | `/api/payments/rajhi/webhook` | `payment.rajhi.webhook` | `Modules\Payment\Http\Controllers\RajhiWebhookController@handle` | No | Public | none | (HasApiResponse helper / raw JSON) |
| GET/POST | `/api/payments/{driver}/{payment}/callback` | `payment.callback` | `Modules\Payment\Http\Controllers\PaymentCallbackController@callback` | No | Public | none | none detected |
| GET/POST | `/api/payments/{driver}/{payment}/redirect` | `payment.redirect` | `Modules\Payment\Http\Controllers\PaymentCallbackController@redirect` | No | Public | none | none detected |

# Support

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| GET | `/api/v1/tickets/{ticketSupport}/conversation` | - | `Modules\Support\Http\Controllers\Api\V1\TicketSupportController@conversation` | auth:sanctum | Sanctum (user or provider) | none | `ConversationMessageCollection` |
| POST | `/api/v1/tickets/{ticketSupport}/conversation` | - | `Modules\Support\Http\Controllers\Api\V1\TicketSupportController@conversationStore` | auth:sanctum | Sanctum (user or provider) | `SendSupportMessageRequest` | `ConversationMessageResource` |
| DELETE | `/api/v1/tickets/{ticketSupport}` | - | `Modules\Support\Http\Controllers\Api\V1\TicketSupportController@destroy` | auth:sanctum | Sanctum (user or provider) | none | (HasApiResponse helper / raw JSON) |
| GET | `/api/v1/tickets/{ticketSupport}` | - | `Modules\Support\Http\Controllers\Api\V1\TicketSupportController@show` | auth:sanctum | Sanctum (user or provider) | none | `TicketSupportResource` |
| GET | `/api/v1/tickets` | - | `Modules\Support\Http\Controllers\Api\V1\TicketSupportController@index` | auth:sanctum | Sanctum (user or provider) | none | `TicketSupportCollection` |
| POST | `/api/v1/tickets` | - | `Modules\Support\Http\Controllers\Api\V1\TicketSupportController@store` | auth:sanctum | Sanctum (user or provider) | `TicketSupportRequest` | `TicketSupportResource` |

# Wallet

| Method | URI | Name | Controller | Auth | Audience | Request | Resources |
|---|---|---|---|---|---|---|---|
| POST | `/api/v1/wallet/add-balance` | - | `Modules\Wallet\Http\Controllers\Api\V1\WalletController@addBalance` | auth:sanctum | Sanctum (user or provider) | `StoreTopUpRequest` | `TopUpResource` |
| GET | `/api/v1/wallet/balance` | - | `Modules\Wallet\Http\Controllers\Api\V1\WalletController@balance` | auth:sanctum | Sanctum (user or provider) | none | `WalletResource` |
| GET | `/api/v1/wallet/transaction` | - | `Modules\Wallet\Http\Controllers\Api\V1\WalletController@transactions` | auth:sanctum | Sanctum (user or provider) | none | `WalletTransactionCollection` |
| POST | `/api/v1/wallet/withdraw` | - | `Modules\Wallet\Http\Controllers\Api\V1\WalletController@withdraw` | auth:sanctum | Sanctum (user or provider) | `StoreWithdrawRequest` | `WithdrawRequestResource` |

