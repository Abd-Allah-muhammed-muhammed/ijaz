# Deferred items (non-mobile-contract)

Items that are intentionally not implemented yet. This file is for backend /
product deferrals that are **not** mobile API contract breaks.

Mobile-breaking payload/contract items live in
[`DEFERRED_MOBILE_BREAKING_CHANGES.md`](./DEFERRED_MOBILE_BREAKING_CHANGES.md).

---

## Order cancellation after payment — wallet holds only; no gateway refund

| Field | Value |
|-------|--------|
| Added | 2026-08-13 |
| Branch | `feat/order-cancellation-wallet-cleanup` |
| Revisit | Adfa Pay gateway integration |

### Current behavior

`CancelOrderPaymentAction` reverses **internal wallet ledger holds** opened on
`PaymentCompleted` (`addPendingDebit` for the user, `adjustPending` for the
provider). The user's card charge is **not** refunded. There is no
`PaymentGatewayInterface::refund()` on any driver (PayTabs, Rajhi, Testing).

This matches `CancelGuarantorAction`: reverse pending holds, do not call Payment.

### Why it's deferred

A real card refund must land on **all** drivers together when Adfa Pay is
integrated, not as a one-off on the current gateways. Until then, cancellation
after payment (when that HTTP flow exists) only unwinds wallet pending
credit/debit.

### Note on reachability

`OrderController::cancel` (user API and provider dashboard) is live for
`InProgress` orders. It transitions to `CancelledByClient` /
`CancelledByProvider`, reverses wallet holds via `CancelOrderPaymentAction`,
and notifies the other party. Offer cancel (`UpdateOfferStatusAction`) remains
blocked once the offer is `Paid` and resets the order to `New` (pre-payment only).
`CancelOrderPaymentAction` refuses orders with `wallet_settled_at` set so
settlement cannot be undone.

### When Adfa Pay lands

1. Add `refund()` to `PaymentGatewayInterface` and every driver.
2. Call it from the cancel-after-payment flow **after** wallet holds are
   reversed (or in the same transaction with a clear compensating path).
3. Keep this entry until that ships.
