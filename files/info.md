# PROMPT 15 A — Remove Laravel Cashier & build the Subscription module

> Re-paste `00-MASTER-CONTEXT.md` before running this prompt.
> Run this **before `15-paymob-billing.md`**. This prompt removes Laravel Cashier and
> builds a **gateway-agnostic subscription module** — the domain brain (plans,
> subscription lifecycle, plan limits, quota metering, events) plus full **admin
> management** in Filament. The Paymob payment mechanics (charging, tokens, webhooks,
> HMAC) live in prompt 15 and plug INTO this module through a small interface.

> **Update the master context:** §2 Payments row → **Paymob (manual), no Cashier**;
> §10 → Paymob env keys (per prompt 15). Note that subscription state is owned by THIS
> module, and any payment gateway is just a driver behind `PaymentGatewayContract`.

---

**Goal:** A clean, self-contained subscription system Wakeel fully controls — no Cashier,
no Stripe assumptions. It defines the plans, owns each company's subscription state
machine, enforces plan limits and conversation quota, emits lifecycle events, and is
fully manageable from the super-admin panel. It is **payment-gateway-agnostic**: Paymob
(prompt 15) implements a driver; tomorrow another gateway could too, without touching
this module.

---

## A. Remove Laravel Cashier (clean teardown)

1. Remove the Cashier package: `composer remove laravel/cashier`.
2. Delete Cashier artifacts: the `Billable` trait usage on `Company`/`User`, the
   published `cashier` config, any Cashier migrations not yet run, and any Stripe env
   keys in `.env`/`.env.example`/`config/services.php`.
3. If Cashier migrations were already migrated (Stripe columns on a table, the
   `subscriptions`/`subscription_items` Cashier tables), write a migration to drop those
   Cashier-specific columns/tables — **our own `subscriptions` schema (below) is the only
   one we keep.** Verify no code references `Cashier`, `Stripe`, `->subscribed()`,
   `->newSubscription()`, etc. (grep and remove).
4. Run the suite to confirm nothing depends on Cashier anymore.

---

## B. Plans (single source of truth)

1. Define the three plans from master-context pricing as a **config-backed catalog**
   (`config/plans.php`) — and optionally a read-only `plans` table seeded from it for
   admin display:
   ```
   starter:   price 9900  (cents/EGP), conversations 500,   numbers 1, units 10,   reps 1
   growth:    price 24900, conversations 2000,  numbers 1, units null, reps 3   (null = unlimited)
   enterprise:price 59900, conversations 10000, numbers 2, units null, reps null
   ```
   Each plan: `key`, `name` (localized), `price_cents`, `currency` (EGP), `period`
   (monthly), and a `limits` map (`conversation_quota`, `numbers`, `units`, `reps`).
2. A `PlanCatalog` service: `all()`, `find($key)`, `limit($key,$type)`. Everything in the
   app reads limits from here — never hard-code a number.

---

## C. Subscription domain (the state machine)

1. **`subscriptions` table:** `company_id` (unique — one active subscription per tenant),
   `plan_key`, `status` (enum), `payment_method` (`card|wallet|valu`, nullable until
   chosen), `gateway` (string, e.g. `paymob`), `gateway_token` (nullable, encrypted —
   the saved card token for recurring), `trial_ends_at` (nullable),
   `current_period_start`, `current_period_end`, `next_charge_at` (nullable),
   `last_payment_at` (nullable), `grace_ends_at` (nullable), `canceled_at` (nullable),
   `cancel_at_period_end` (bool), timestamps.
2. **`SubscriptionStatus` enum** (string-backed): `trialing`, `active`, `past_due`,
   `canceled`, `expired`. Define allowed transitions explicitly:
   ```
   trialing  → active | canceled | expired
   active    → past_due | canceled
   past_due  → active | canceled | expired
   canceled  → (terminal, but may be reactivated into a new subscription)
   expired   → (terminal)
   ```
3. **`Subscription` model** with the `BelongsToCompany` trait and rich helpers:
   `isActive()` (active or trialing within period), `onTrial()`, `isPastDue()`,
   `onGrace()`, `hasEnded()`, `daysUntilRenewal()`. Guard transitions through a
   `transitionTo(SubscriptionStatus)` method that rejects illegal moves and fires the
   matching event.
4. **`SubscriptionManager` service** — the public API the rest of the app + the payment
   gateway use:
   - `startTrial(Company, planKey)` → trialing.
   - `activate(Company, planKey, method, gateway, ?token, periodStart, periodEnd)` →
     active (called after a verified first payment).
   - `renew(Subscription, periodStart, periodEnd)` → extend, set next_charge_at.
   - `markPastDue(Subscription)` → start dunning/grace.
   - `cancel(Subscription, atPeriodEnd=true)` and `resume()`.
   - `changePlan(Subscription, newPlanKey, strategy)` → upgrade (immediate or prorate)
     / downgrade (at next period) — pick & document a simple strategy for MVP
     (upgrades immediate, downgrades next cycle).
   - `expire(Subscription)` → terminal when grace lapses.
   All methods are idempotent and emit events.

---

## D. Lifecycle events (decoupled side effects)

Fire and listen:
- `SubscriptionActivated` → set company `is_active = true`; send localized welcome/receipt.
- `SubscriptionRenewed` → record, optional receipt.
- `SubscriptionPastDue` → start dunning (notify owner, schedule retries — actual charging
  retries belong to the gateway/prompt 15).
- `SubscriptionCanceled` / `SubscriptionExpired` → set company `is_active = false` at
  period end; notify; stop the bot per policy (decide: read-only dashboard vs full
  suspend — document).
- `PlanChanged` → adjust enforced limits immediately.
- All notifications localized via prompt 18; all admin-relevant transitions written to
  the audit log (prompt 16).

---

## E. Plan-limit & quota enforcement

1. **`PlanGate` service** (used by app + admin): `withinLimit(Company,$type,$intended)`
   for `units`, `reps`, `numbers` (null limit = unlimited). Block at the UI and
   server-side (e.g. 11th unit on Starter, 2nd rep, extra number → rejected with a
   localized "upgrade your plan" message).
2. **Conversation quota metering:** a `conversation_usage` counter per
   (`company_id`, period) — a billable conversation = a customer thread with ≥1 bot/rep
   reply within the current period. Increment from the inbound pipeline (prompt 05/08)
   once per conversation per period. `quotaUsed(Company)` / `quotaRemaining(Company)`.
   At/over quota: warn the owner; **MVP policy: keep replying but flag overage** and
   prompt upgrade (document; make it a config switch so it can become a hard stop later).
3. Reset/roll usage at each `current_period_*` boundary.
4. Expose helpers the dashboard (prompt 13) and admin (prompt 16) read for usage bars.

---

## F. Gateway abstraction (so Paymob plugs in cleanly)

1. Define `PaymentGatewayContract` with the operations the module needs:
   `startCheckout(Company, planKey, method): CheckoutSession`,
   `chargeRecurring(Subscription, amountCents): ChargeResult`,
   `verifyWebhook(Request): WebhookEvent`. The module calls the contract; it does NOT
   know about Paymob.
2. Bind the implementation by config (`PAYMENT_GATEWAY=paymob`). Prompt 15 provides the
   `PaymobGateway` implementing this contract (auth → order → payment key → iframe,
   saved-token charge, HMAC-verified webhook) and calls back into `SubscriptionManager`
   (`activate`, `renew`, `markPastDue`) on verified events.
3. The **recurring scheduler** (`billing:charge-due`, daily) lives here at the module
   level: find subs with `next_charge_at <= now` in `active|past_due`, call
   `gateway->chargeRecurring(...)`, then `renew()` on success or `markPastDue()` → grace →
   `expire()` on repeated failure. Card subs charge silently (saved token); wallet/Valu
   subs get a monthly payment link (the gateway builds it) and stay `past_due` until paid.
   Idempotent per (subscription, period) with a lock.

---

## G. Admin management (Filament — extends expanded prompt 16)

In the super-admin panel:
1. **Subscriptions resource:** list every tenant's subscription — company, plan badge,
   status, method (card/wallet/Valu), next charge date, current period, MRR contribution,
   over-quota flag. Filters: status, plan, method, past_due/dunning, trialing, canceling.
2. **Subscription detail:** full state, period history, linked `payment_transactions`
   (from prompt 15), usage this cycle. Manual **actions** (all audited + confirmation):
   start/extend trial, change plan, grant bonus quota, retry a failed recurring charge,
   mark paid (for wallet/Valu reconciliation), cancel (immediate or at period end),
   reactivate.
3. **Billing dashboard widgets:** MRR + trend, active vs trialing vs past_due counts,
   churn this month, dunning queue (past_due tenants with retry status), method breakdown
   (card vs wallet vs Valu), failed-charge alerts. Margin (MRR vs Paymob fees + Claude
   cost) ties into prompt 16's revenue-vs-cost widget.
4. Past-due / dunning tenants surfaced as actionable alerts; clicking jumps to the
   subscription.

---

## H. Tenant-facing billing screen (handoff to prompt 15)

This module owns the data and lifecycle; the customer-facing `/dashboard/billing` UI
(plan picker, method choice, invoice list, upgrade/cancel) is detailed in prompt 15
since it drives the Paymob checkout. Ensure the screen reads subscription state and usage
from THIS module's services.

---

**Tests:**
- Cashier fully removed: no `laravel/cashier` dependency, no Stripe references, suite
  green.
- State machine rejects illegal transitions (e.g. `expired → active`) and accepts legal
  ones; each transition fires its event exactly once.
- `SubscriptionManager.activate/renew/cancel/changePlan/expire` are idempotent.
- `PlanGate`: 11th unit on Starter blocked, 2nd rep blocked, extra number blocked;
  unlimited (null) limits never block.
- Quota: increments once per billable conversation per period, `quotaRemaining` correct,
  resets at period boundary, overage flagged per policy.
- Gateway abstraction: a fake gateway drives `activate → renew → past_due → expire`
  end-to-end without any Paymob code (proves decoupling).
- Recurring scheduler charges due card subs (fake gateway), renews on success, enters
  dunning then expires on repeated failure; idempotent per period.
- Admin: status changes write audit entries; manual actions gated + confirmed; MRR /
  dunning widgets compute correctly from seeded data.

**Done when:** Cashier is gone, Wakeel owns a clean gateway-agnostic subscription module
with a guarded state machine, plan-limit + quota enforcement, lifecycle events, a daily
recurring engine, and full audited admin management — ready for prompt 15's Paymob driver
to plug in.
