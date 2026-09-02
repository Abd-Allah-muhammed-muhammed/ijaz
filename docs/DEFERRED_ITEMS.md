# Deferred Items — Things to Revisit

Tracked items explicitly deferred during development (deliberate
decisions, not oversights). Grouped by whether they depend on Edfapay
integration or not.

## Depends on Edfapay (payment gateway) integration

- **Guarantor: gateway refund/reversal.** Admin cancel reverses internal
  wallet holds only — the original card charge is not refunded
  automatically. Documented as a known limitation in
  docs/GUARANTOR_MOBILE_API.md §9.
- **Orders: gateway refund on post-payment cancel/dispute resolution.**
  Same limitation — "full to client" and "escalate" dispute outcomes
  reverse internal wallet holds only, no gateway refund. Documented in
  docs/ORDERS_MOBILE_API.md.
- **Guarantor: PayoutMethod design** (how a provider/requester's payout
  bank details get stored) — deferred pending a decision on whether to
  use a Mass Payout provider (Upwork-style) or local encrypted storage.
  Edfapay confirmed as merchant-level escrow only, not a per-recipient
  payout API — this decision needs to happen alongside/after Edfapay
  integration.

## Not dependent on Edfapay

- **Opportunity: dead lifecycle statuses** (`in_progress`, `ended`,
  `cancelled` on OpportunityStatusEnum) — defined but no Action reaches
  them. Left as-is; could be activated later if the product needs a
  richer Opportunity lifecycle beyond new → offer_accepted → expired.
- **Orders: no dedicated JSON API for Provider actions** (submit/edit/
  end/cancel offer) — these currently only work via the Provider web
  dashboard (Inertia), not `/api/v1` routes. Likely intentional (no
  separate Provider mobile app today), but flagged here since it wasn't
  an explicit product confirmation — revisit if a Provider mobile app
  is ever planned.
- **Orders/Guarantor: no active dispute-during-InProgress mechanism
  beyond what was built today.** Both now have dispute resolution
  (4-path, admin-only) — this line item is closed, kept here only as a
  cross-reference in case future work wants a THIRD dispute
  implementation (e.g. for Jobs) — see the note below on NOT building a
  shared/generic dispute engine prematurely.
- **No shared/generic "dispute engine" across modules.** Guarantor and
  Orders each have their own independent dispute implementation
  (deliberately not sharing code) — discussed and decided against
  premature abstraction, since the two modules' data models differ more
  than they first appear to. Revisit ONLY if a third module needs
  dispute resolution — with two real implementations to compare, a
  genuine shared abstraction (if warranted) can be extracted with real
  information instead of a guess.
- **Bank name translations (hi/ur locales)** in `BanksSeeder` are
  best-effort transliterations (e.g. STC Bank, D360 Bank kept as Latin
  acronyms) — flagged for native-speaker review before these are
  user-facing in production.
- **Website is not CMS-driven** — the 5 legal/info pages
  (privacy-and-policies, privacy-policy, how-to-use-agency,
  real-estate-marketplace-terms-of-use,
  service-provider-authorization-terms-and-conditions) were reverted to
  their original static components + lang JSON content. The website
  will be rebuilt as a separate future project; do not incrementally
  CMS-ify it in the meantime. The 4 underlying CMS pages (privacy,
  service-provider-authorization, how-to-use-agency,
  real-estate-marketplace-terms) remain available in the CMS/database
  for the mobile `terms` endpoint and any future website rebuild.
- **Redis as active cache driver** — installed but not yet the primary
  cache driver in production; pending local Docker environment
  confirmation (from earlier project context, unrelated to today's work).
- **Admin Web Push plumbing** — `HasDeviceTokens` on the Admin model,
  a web token registration endpoint, and the `systems.1` broadcast
  channel in MasterLayout are not yet wired for withdraw/offline-top-up/
  provider-registration-pending-review admin notifications (from
  earlier project context).
- **Orders: admin repair tooling** — considered and explicitly dropped
  (not deferred) after review: the underlying data-integrity bugs are
  now fixed at the root (see the Order/Offer flow integrity audit), so
  new corrupted rows shouldn't occur going forward, and manually
  repairing old test-environment rows was judged not worth building
  tooling for, since this data will be wiped before production anyway.

## Historical/reference only (already resolved, kept for context)

- 3 real production Guarantor rows were found paid via the wrong
  endpoint (Individual lump-sum instead of installment-by-installment)
  before the type-guard fix shipped. Left unremediated per explicit
  decision (test data). Identification query is in the relevant session
  transcript if ever needed.
- 3 real Orders rows were found with `accepted_offer_id` pointing at a
  rejected offer (root cause: reject-after-accept desync, now fixed).
  Also left unremediated per the same test-data policy.
