# API Inventory

Last verified from source: 2026-04-16

Generated from routes/api.php, routes/Api/V1/*.php, and controller/request/resource usage.

Notes:
- Auth column shows middleware guard requirements.
- Request fields are from FormRequest classes where used.
- Response structure is from JsonResource/collection or response helper usage in controllers.
- If a field/shape is not explicit in code, it is marked Unknown.
- Response shapes: The actual response shape used by the code is not the simple `{ status, message, data }` shape. The project uses `MMAE\ApiResponse\Traits\HasApiResponse`, with controller calls showing shapes such as `successResponse(...)`, `successMessageResponse(...)`, `successResponseWithToken(...)`, and `failedMessageResponse(...)`.

## Authentication

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| POST | /api/v1/user/auth/login | `Api\V1\User\AuthController@login` | No | `LoginRequest` | none | Sends login OTP to a user phone, revokes prior tokens, creates a 15-minute login token. |
| POST | /api/v1/user/auth/register | `Api\V1\User\AuthController@register` | No | `RegisterRequest` | `UserResource` | Creates the user, sends OTP, returns a token and user resource. |
| GET | /api/v1/user/auth/me | `Api\V1\User\AuthController@auth` | Yes (`auth:user-api`) | none | `UserResource` | Returns authenticated user. |
| POST | /api/v1/user/auth/profile/update | `Api\V1\User\AuthController@profileUpdate` | Yes (`auth:user-api`) | `UpdateRequest` | `UserResource` | Updates user profile and image. |
| POST | /api/v1/user/auth/logout | `Api\V1\User\AuthController@logout` | Yes (`auth:user-api`) | none | none | Deletes all tokens for current user. |
| POST | /api/v1/otp/send | `Api\V1\OtpController@send` | Yes (`auth:sanctum`) | `SendOTPRequest` | none | Generates and sends OTP. |
| POST | /api/v1/otp/verify | `Api\V1\OtpController@verify` | Yes (`auth:sanctum`) | `VerifyOTPRequest` | user/provider resources | Verifies OTP and returns the verified actor. |

### Request body fields:
- POST /api/v1/user/auth/login: `LoginRequest` (phone)
- POST /api/v1/user/auth/register: `RegisterRequest` (f_name, l_name, email, phone, nationality_id, image, latitude, longitude, password nullable)
- POST /api/v1/user/auth/profile/update: `UpdateRequest` (f_name, l_name, email, password+confirmation, phone, nationality_id, image nullable)
- POST /api/v1/otp/send: `SendOTPRequest`
- POST /api/v1/otp/verify: `VerifyOTPRequest` (type, otp)

### Response structures:
- login/register: `HasApiResponse` helper payload, register includes token and `UserResource`
- me/profile update: `UserResource`
- otp verify: `UserResource` or `ProviderResource` depending verified actor type
- logout: response helper message

## Catalog

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| GET | /api/v1/catalog/categories | `Api\V1\CatalogController@categories` | No | none | `CategoryCollection` | Lists root or filtered categories with children. |
| GET | /api/v1/catalog/categories/with-no-children | `Api\V1\CatalogController@categoriesWithNoChildren` | No | none | `CategoryCollection` | Lists leaf categories. |
| GET | /api/v1/catalog/categories/{category}/children | `Api\V1\CatalogController@categoryChildren` | No | none | `CategoryCollection` | Lists category children. |
| GET | /api/v1/catalog/categories/{category}/skills | `Api\V1\CatalogController@categorySkills` | No | none | `SkillCollection` | Lists skills for a category or all skills when `id=0`. |
| GET | /api/v1/catalog/regions | `Api\V1\CatalogController@regions` | No | none | `RegionCollection` | Lists regions. |
| GET | /api/v1/catalog/regions/{region}/cities | `Api\V1\CatalogController@cities` | No | none | `CityCollection` | Lists cities for a region. |
| GET | /api/v1/catalog/provider-types | `Api\V1\CatalogController@providerTypes` | No | none | raw/collection-like | Returns provider types. |
| GET | /api/v1/catalog/nationalities | `Api\V1\CatalogController@nationalities` | No | none | `NationalityCollection` | Lists nationalities. |
| GET | /api/v1/catalog/providers | `Api\V1\CatalogController@providers` | No | none | `ProviderResource` | Looks up provider by phone and optional category. |
| GET | /api/v1/catalog/banners | `Api\V1\CatalogController@banners` | No | none | `BannerResource::collection` | Lists banners. |
| GET | /api/v1/catalog/pages | `Api\V1\CatalogController@pages` | No | none | raw mapped array | Lists pages with `id`, `slug`, `title`. |
| GET | /api/v1/catalog/pages/{page} | `Api\V1\CatalogController@page` | No | none | `PageResource` | Returns one page. |
| GET | /api/v1/catalog/settings | `Api\V1\CatalogController@settings` | No | none | raw array | Returns `app('settings')->toArray()`. |
| GET | /api/v1/catalog/questions | `Api\V1\CatalogController@questions` | No | none | `QuestionCollection` | Lists questions. |

## User Orders

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| GET | /api/v1/user/orders | `Api\V1\User\OrderController@index` | Yes (`auth:user-api` + `abilities:user-api`) | none | `OrderCollection` | Lists current user's orders with offer counts. |
| POST | /api/v1/user/orders | `Api\V1\User\OrderController@store` | Yes | `OrderRequest` | `OrderResource` | Creates a new order and dispatches notifications/events. |
| GET | /api/v1/user/orders/{order} | `Api\V1\User\OrderController@show` | Yes | none | `OrderResource` | Returns order with offers/provider/media/skills. |
| POST | /api/v1/user/orders/{order}/edit | `Api\V1\User\OrderController@edit` | Yes | `OrderRequest` | `OrderResource` | Updates an order only while status is `New`. |
| POST | /api/v1/user/orders/{order}/{offer}/update-status | `Api\V1\User\OrderController@updateOfferStatus` | Yes | `UpdateOfferStatusRequest` | helper response | Updates offer status and may update order status, fees, and notifications. |
| POST | /api/v1/user/orders/{order}/{offer}/pay | `Api\V1\User\OrderController@pay` | Yes | none | payment array | Creates a `Payment` for the accepted offer and triggers gateway processing. |
| POST | /api/v1/user/orders/{order}/end-and-review | `Api\V1\User\OrderController@endAndReview` | Yes | `EndAndReviewRequest` | helper response | Marks order ended by client and writes review. |
| DELETE | /api/v1/user/orders/{order}/{media:uuid}/delete | `Api\V1\User\OrderController@deleteMedia` | Yes | none | helper response | Deletes order media after ownership/status checks. |
| DELETE | /api/v1/user/orders/{order} | `Api\V1\User\OrderController@destroy` | Yes | none | helper response | Deletes order if it has no offers. |

### Request body fields:
- create/edit: `OrderRequest` (title, description nullable, budget_start, budget_end, category_id, files nullable array, provider_id nullable, expected_time nullable, skills array)
- update-status: `UpdateOfferStatusRequest` (status enum)
- end-and-review: `EndAndReviewRequest` (rating, comment)
- pay: no dedicated FormRequest (inline checks)

## User Provider Lookup

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| GET | /api/v1/user/providers/get | `Api\V1\User\ProviderController@get` | Yes (`auth:user-api` + `abilities:user-api`) | `findProviderRequest` | `ProviderResource` | Looks up a provider by request parameters. |

## Chats

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| GET | /api/v1/chats | `Api\V1\ChatController@index` | Yes (`auth:sanctum`) | none | `ConversationCollection` | Lists chats. |
| POST | /api/v1/chats | `Api\V1\ChatController@store` | Yes | `StoreConversationRequest` | `ConversationResource` | Creates/open a direct conversation. |
| POST | /api/v1/chats/send/{conversation} | `Api\V1\ChatController@send` | Yes | `SendMessageRequest` | `ConversationMessageResource` | Sends a direct chat message. |
| GET | /api/v1/chats/{conversation} | `Api\V1\ChatController@show` | Yes | none | `ConversationMessageCollection` | Lists messages in a conversation. |
| GET | /api/v1/chats/{conversation}/show | `Api\V1\ChatController@chat` | Yes | none | `ConversationResource` | Returns conversation detail variant. |
| GET | /api/v1/chats/guarantee | `Api\V1\ChatController@guaranteeChatIndex` | Yes | none | `ConversationCollection` | Lists guarantee chats. |
| POST | /api/v1/chats/guarantee | `Api\V1\ChatController@guaranteeChatStore` | Yes | raw `Request` | `ConversationResource` | Creates guarantee conversation. |
| GET | /api/v1/chats/orders | `Api\V1\OrderChatController@index` | Yes | none | `ConversationCollection` | Lists order chats. |
| POST | /api/v1/chats/orders | `Api\V1\OrderChatController@store` | Yes | inline validation | `ConversationResource` | Creates order conversation. ⚠️ Needs dedicated FormRequest |
| POST | /api/v1/chats/orders/send/{conversation} | `Api\V1\OrderChatController@send` | Yes | `SendMessageRequest` | `ConversationMessageResource` | Sends order message. |
| GET | /api/v1/chats/orders/{conversation} | `Api\V1\OrderChatController@show` | Yes | none | `ConversationMessageCollection` | Lists order conversation messages. |
| GET | /api/v1/chats/tickets | `Api\V1\TicketSupportChatController@index` | Yes | none | `ConversationCollection` | Lists ticket chats. |
| POST | /api/v1/chats/tickets/send/{conversation} | `Api\V1\TicketSupportChatController@send` | Yes | `SendMessageRequest` | `ConversationMessageResource` | Sends ticket message. |
| GET | /api/v1/chats/tickets/{conversation} | `Api\V1\TicketSupportChatController@show` | Yes | none | `ConversationMessageCollection` | Lists ticket messages. |

## Jobs

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| GET | /api/v1/jobs/all | `Api\V1\JobController@all` | Yes (`auth:sanctum`) | none | `JobCollection` | Lists all active jobs. |
| GET | /api/v1/jobs | `Api\V1\JobController@index` | Yes (`auth:sanctum`) | none | `JobCollection` | Lists own jobs. |
| POST | /api/v1/jobs | `Api\V1\JobController@store` | Yes | `JobRequest` | `JobResource` | Creates a new job. |
| GET | /api/v1/jobs/{job} | `Api\V1\JobController@show` | Yes | none | `JobResource` | Returns job detail. |
| PUT/PATCH | /api/v1/jobs/{job} | `Api\V1\JobController@update` | Yes | `JobRequest` | `JobResource` | Updates a job. |
| DELETE | /api/v1/jobs/{job} | `Api\V1\JobController@destroy` | Yes | none | helper response | Deletes a job. |
| DELETE | /api/v1/jobs/{job}/media/{media} | `Api\V1\JobController@destroyMedia` | Yes | none | helper response | Deletes job media. |

### Request body fields:
- create/update: `JobRequest` (title, description, expected_salary, expired_at, contact_number, city_id, region_id, nationality_id, type enum, files nullable, skills nullable)

## Guarantee Requests

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| GET | /api/v1/guarantee-requests | `Api\V1\GuaranteeRequestController@index` | Yes (`auth:sanctum`) | none | `GuaranteeRequestCollection` | Lists guarantee requests. |
| GET | /api/v1/guarantee-requests/assigned | `Api\V1\GuaranteeRequestController@assigned` | Yes | none | `GuaranteeRequestCollection` | Lists assigned guarantee requests. |
| POST | /api/v1/guarantee-requests | `Api\V1\GuaranteeRequestController@store` | Yes | `GuaranteeRequestRequest` | `GuaranteeRequestResource` | Creates guarantee request. |
| GET | /api/v1/guarantee-requests/{guaranteeRequest} | `Api\V1\GuaranteeRequestController@show` | Yes | none | `GuaranteeRequestResource` | Returns guarantee detail. |
| POST | /api/v1/guarantee-requests/{guaranteeRequest}/edit | `Api\V1\GuaranteeRequestController@edit` | Yes | `GuaranteeRequestRequest` | `GuaranteeRequestResource` | Updates guarantee request. |
| POST | /api/v1/guarantee-requests/{guaranteeRequest}/update-status | `Api\V1\GuaranteeRequestController@updateStatus` | Yes | `UpdateGuaranteeRequestStatusRequest` | helper response | Updates guarantee status. ⚠️ Status transition logic embedded in controller |
| POST | /api/v1/guarantee-requests/{guaranteeRequest}/pay | `Api\V1\GuaranteeRequestController@pay` | Yes | none | payment array | Initiates payment for guarantee. |
| DELETE | /api/v1/guarantee-requests/{guaranteeRequest}/media/{media:uuid} | `Api\V1\GuaranteeRequestController@deleteMedia` | Yes | none | helper response | Deletes guarantee media. |
| DELETE | /api/v1/guarantee-requests/{guaranteeRequest} | `Api\V1\GuaranteeRequestController@destroy` | Yes | none | helper response | Deletes guarantee request. |

### Request body fields:
- create/edit: `GuaranteeRequestRequest` (title, provider_type, phone, description, amount, files nullable)
- update-status: `UpdateGuaranteeRequestStatusRequest` (status enum)

## Advisements

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| GET | /api/v1/property-advisements/all | `Api\V1\PropertyAdvisementController@all` | No | none | `PropertyAdvisementCollection` | Public property listings. |
| GET | /api/v1/property-advisements | `Api\V1\PropertyAdvisementController@index` | Yes (`auth:sanctum`) | none | `PropertyAdvisementCollection` | Own property listings. |
| POST | /api/v1/property-advisements | `Api\V1\PropertyAdvisementController@store` | Yes | `PropertyAdvisementRequest` | `PropertyAdvisementResource` | Creates property listing. |
| GET | /api/v1/property-advisements/{propertyAdvisement} | `Api\V1\PropertyAdvisementController@show` | Yes | none | `PropertyAdvisementResource` | Property detail. |
| POST | /api/v1/property-advisements/{propertyAdvisement}/edit | `Api\V1\PropertyAdvisementController@edit` | Yes | `PropertyAdvisementRequest` | `PropertyAdvisementResource` | Edits property listing. |
| DELETE | /api/v1/property-advisements/{propertyAdvisement}/media/{media:uuid} | `Api\V1\PropertyAdvisementController@deleteMedia` | Yes | none | helper response | Deletes property media. |
| DELETE | /api/v1/property-advisements/{propertyAdvisement} | `Api\V1\PropertyAdvisementController@destroy` | Yes | none | helper response | Deletes property listing. |
| GET | /api/v1/car-advisements/all | `Api\V1\CarAdvisementController@all` | No | none | `CarAdvisementCollection` | Public car listings. |
| GET | /api/v1/car-advisements | `Api\V1\CarAdvisementController@index` | Yes (`auth:sanctum`) | none | `CarAdvisementCollection` | Own car listings. |
| POST | /api/v1/car-advisements | `Api\V1\CarAdvisementController@store` | Yes | `CarAdvisementRequest` | `CarAdvisementResource` | Creates car listing. |
| GET | /api/v1/car-advisements/{carAdvisement} | `Api\V1\CarAdvisementController@show` | Yes | none | `CarAdvisementResource` | Car detail. |
| POST | /api/v1/car-advisements/{carAdvisement}/edit | `Api\V1\CarAdvisementController@edit` | Yes | `CarAdvisementRequest` | `CarAdvisementResource` | Edits car listing. |
| DELETE | /api/v1/car-advisements/{carAdvisement}/media/{media:uuid} | `Api\V1\CarAdvisementController@deleteMedia` | Yes | none | helper response | Deletes car media. |
| DELETE | /api/v1/car-advisements/{carAdvisement} | `Api\V1\CarAdvisementController@destroy` | Yes | none | helper response | Deletes car listing. |

### Request body fields:
- property create/edit: `PropertyAdvisementRequest` (title, description, operation enum, property_type_id, city_id, region_id, category_id nullable, price, show_price, area, bedrooms_count, bathrooms_count, halls_count, age, facade, street_width, street_type, phone, license, address, latitude, longitude, options, files)
- car create/edit: `CarAdvisementRequest` (title, description, operation enum, usage_status enum, car_brand_id, car_type_id, car_category_id nullable, city_id, region_id, year, mileage nullable, transmission, fuel_type, engine_size, color, price, show_price, phone, address, latitude, longitude, options, files)

⚠️ Note: Filtering logic in `index()` and `all()` is duplicated between controllers.

## Tickets

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| GET | /api/v1/tickets | `Api\V1\TicketController@index` | Yes (`auth:sanctum`) | none | `TicketSupportCollection` | Lists support tickets. |
| POST | /api/v1/tickets | `Api\V1\TicketController@store` | Yes | `TicketSupportRequest` | `TicketSupportResource` | Creates support ticket. |
| GET | /api/v1/tickets/{ticketSupport} | `Api\V1\TicketController@show` | Yes | none | `TicketSupportResource` | Ticket detail. |
| DELETE | /api/v1/tickets/{ticketSupport} | `Api\V1\TicketController@destroy` | Yes | none | helper response | Deletes ticket. |
| GET | /api/v1/tickets/{ticketSupport}/conversation | `Api\V1\TicketController@conversation` | Yes | none | `ConversationMessageCollection` | Lists ticket conversation. |
| POST | /api/v1/tickets/{ticketSupport}/conversation | `Api\V1\TicketController@sendConversation` | Yes | `SendSupportMessageRequest` | `ConversationMessageResource` | Sends ticket message. |

### Request body fields:
- create ticket: `TicketSupportRequest` (title, message, operation_type nullable, operation_id nullable)
- send conversation message: `SendSupportMessageRequest` (content, files nullable)

## Wallet

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| GET | /api/v1/wallet/balance | `Api\V1\WalletController@balance` | Yes (`auth:sanctum`) | none | `WalletResource` | Wallet summary/balance. |
| POST | /api/v1/wallet/add-balance | `Api\V1\WalletController@addBalance` | Yes | `StoreTopUpRequest` | mixed payload | Creates top-up request/payment initiation. |
| POST | /api/v1/wallet/withdraw | `Api\V1\WalletController@withdraw` | Yes | `StoreWithdrawRequestRequest` | mixed payload | Creates withdraw request. |
| GET | /api/v1/wallet/transaction | `Api\V1\WalletController@transaction` | Yes | none | `WalletTransactionCollection` | Lists wallet transactions. |

### Request body fields:
- add-balance: `StoreTopUpRequest` (amount, payment_method enum, transaction_image required_if offline, user_notes)
- withdraw: `StoreWithdrawRequestRequest` (amount, user_notes)

### Response structures:
- balance: `WalletResource`
- add-balance: mixed payload (payment response fields + `TopUpResource`)
- withdraw: mixed payload with `WithdrawRequestResource`
- transactions: `WalletTransactionCollection`

⚠️ Note: Add-balance and withdraw flows return different response shapes for similar operations.

## Notifications and Settings

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| GET | /api/v1/auth/counts | `Api\UserController@counts` | Yes (`auth:sanctum`) | none | raw counters | Unread counts summary. |
| GET | /api/v1/auth/notifications | `Api\UserController@notifications` | Yes | none | `NotificationCollection` | Lists notifications. |
| GET | /api/v1/auth/notifications/mark-all-as-read | `Api\UserController@markAllNotificationsAsRead` | Yes | none | helper response | Mark all read. |
| GET | /api/v1/auth/notifications/{notification}/mark-as-read | `Api\UserController@markNotificationAsRead` | Yes | none | helper response | Mark one read. |
| DELETE | /api/v1/auth/notifications/all | `Api\UserController@deleteAllNotifications` | Yes | none | helper response | Delete all notifications. |
| DELETE | /api/v1/auth/notifications/{notification} | `Api\UserController@deleteNotification` | Yes | none | helper response | Delete notification. |
| POST | /api/v1/auth/update-settings | `Api\UserController@updateSettings` | Yes | `UpdateSettingsRequest` | helper response | Update user settings. |
| GET | /api/v1/auth/delete-account | `Api\UserController@deleteAccount` | Yes | none | helper response | Delete account. |

⚠️ Note: Notification and account deletion endpoints use `GET` for state-changing operations; should use mutation-safe HTTP verbs.

## Messages

| Method | Endpoint | Controller | Auth | Request | Resource | Description |
|---|---|---|---|---|---|---|
| POST | /api/v1/messages | `Api\V1\MessageController@store` | Yes (`auth:sanctum`) | `MessagRequest` | helper response | Store contact/message record. |

### Request body fields:
- `MessagRequest` (name, phone, title, content)

## Non-V1 API Endpoints

| Method | Endpoint | Controller | Auth | Description |
|---|---|---|---|---|
| GET/POST | /api/payments/paytabs/{payment}/redirect | `Api\PaymentController@redirect` | No | Payment redirect endpoint |
| GET/POST | /api/payments/paytabs/{payment}/callback | `Api\PaymentController@callback` | No | Payment callback endpoint |
| GET | /api/categories | `Api\CatalogController@categories` | No | Dashboard filter categories |
| GET | /api/skills | `Api\CatalogController@skills` | No | Dashboard filter skills |
| GET | /api/regions | `Api\CatalogController@regions` | No | Dashboard filter regions |
| GET | /api/cities | `Api\CatalogController@cities` | No | Dashboard filter cities |
| GET | /api/provider-types | `Api\CatalogController@providerTypes` | No | Dashboard filter provider types |
| GET/POST | /api/broadcasting/auth | Broadcasting auth route | Yes (`auth:sanctum`) | Broadcast authentication |

