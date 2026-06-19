# Refactor Notes

Last verified from source: 2026-04-16

## Issues Found

| File | Issue | Severity | Suggestion |
|---|---|---|---|
| app/Http/Controllers/Api/V1/User/OrderController.php | Controller mixes validation, business rules, fee calculation, payment initiation, and media handling in one class. | High | Extract offer/payment/review flow into Services or Actions. |
| app/Http/Controllers/Api/V1/CarAdvisementController.php | `index()` and `all()` duplicate a large filtering pipeline. | Medium | Move repeated filter logic into a shared query scope or filter class. |
| app/Http/Controllers/Api/V1/PropertyAdvisementController.php | `index()` and `all()` duplicate a large filtering pipeline. | Medium | Move repeated filter logic into a shared query scope or filter class. |
| app/Http/Controllers/Api/V1/CatalogController.php | Provider lookup path does multiple queries and manual response shaping. | Medium | Reuse query result and move provider lookup mapping into a resource. |
| app/Http/Controllers/Api/V1/OrderChatController.php | `store()` uses inline validation instead of a FormRequest. | Medium | Add a FormRequest for conversation creation. |
| app/Http/Controllers/Api/V1/ChatController.php | Several methods use raw `Request` and large branching logic for different chat targets. | Medium | Split by chat type and add FormRequests for each input shape. |
| app/Http/Controllers/Api/UserController.php | Notifications and account deletion actions are implemented as controller-level flow, with GET endpoints for state changes. | Medium | Use mutation-safe HTTP verbs and consider request classes for settings/account operations. |
| app/Http/Controllers/Api/V1/JobController.php | `store()`/`update()` and filtering logic live together with media processing. | Medium | Extract media handling and query filtering into Actions or Services. |
| app/Http/Controllers/Api/V1/WalletController.php | Add-balance and withdraw flows return different response shapes for similar operations. | Medium | Introduce dedicated response resources or DTOs for wallet operations. |
| app/Http/Controllers/Api/V1/MessageController.php | Contact/message storage is handled directly in the controller. | Low | Keep validation in a FormRequest and consider a small service if logic grows. |
| app/Http/Controllers/Api/V1/OtpController.php | OTP handling is controller-driven and tightly coupled to verification side effects. | Medium | Extract OTP generation/verification into a service if more flows are added. |
| app/Traits/HasWallet.php | Method name `walletTTransactions()` is misspelled and propagates to callers. | Medium | Rename to `walletTransactions()` and update references. |
| app/Models/PropertiyCategory.php | Model name is misspelled. | Medium | Rename to `PropertyCategory` if you can absorb the migration/refactor cost. |
| app/Models/Conversation.php | `lastMassage` appears to be a typo for `lastMessage`. | Medium | Rename the relation/method and update all references. |
| app/Enums/Payment/PaymentDriverEnum.php | Testing driver is embedded as an enum case with hardcoded fee logic. | Low | Move fees/configuration to config if environment-specific behavior grows. |
| app/Traits/HasOTPs.php | `markPhoneAsVerified()` is a TODO stub. | Medium | Implement or remove the method to avoid false expectations. |
| app/Actions/Payment/* | Payment actions follow a pipeline style, but notification stubs do nothing. | Low | Implement the notification actions or throw explicit not-implemented exceptions. |
| app/Http/Requests/Api/Api/User/*.php | Request files live under a duplicated `Api/Api/User` path. | Medium | Re-home them under a single namespace path such as `Api/V1/User`. |
| routes/web.php | A few routes use non-RESTful verbs for state changes and payment callbacks. | Low | Keep if intentional, but document the deviation and consider aligning verbs where possible. |

## Biggest Risks

- The order and guarantee payment/status flows are the highest-risk areas because they combine state transitions, wallet effects, and payment provider responses.
- The duplicated filtering logic in advisement controllers will become expensive to maintain as filters grow.
- The request namespace mismatch and model typos are refactor traps for future contributors and will complicate dedoc/scramble generation.
- Response shapes are not fully uniform across endpoints, which will make client SDK generation harder.

## Notes

- Validation is generally centralized in FormRequest classes, but several endpoints still use inline validation or route-time business checks.
- Resource usage is strong overall, but a few endpoints still return raw arrays or mixed payloads.
- I did not rewrite unrelated code paths; this list focuses on issues visible in the analyzed files.

## Missing Items / Future Improvements

| Item | Why It Matters | Priority |
|---|---|---|
| dedoc/scramble documentation | Better client integration, SDK generation | Medium |
| Unified API response envelope | Client consistency, error handling | Medium |
| Fully centralized service/action layer for order/guarantee/payment workflows | Logic is currently scattered between controllers and traits | High |
| Dedicated Job directory with discovered async jobs | Current implementation or location unclear | Low |
| Fully standardized request namespace structure | Currently has duplicated `Api/Api/User` path | Medium |
