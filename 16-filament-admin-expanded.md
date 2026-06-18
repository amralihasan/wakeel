# PROMPT 16 (EXPANDED) — Filament v3 super-admin panel (complete)

> Re-paste `00-MASTER-CONTEXT.md` before running this prompt.
> **This supersedes the brief prompt 16** in `11-17-dashboard-saas.md`. It builds the
> full platform-owner control center at `/admin` — YOUR cockpit for running the entire
> Wakeel SaaS: every tenant, every WhatsApp number, usage, cost, revenue, conversations,
> system health, and audit trail. This is NOT the client dashboard (that's prompts
> 10–15); this is the operator panel only super-admins see.
> Run after the core platform exists (after prompt 15 ideally) so there's real data to
> manage; localize it via prompt 18 if that's already run.

---

**Goal:** From `/admin` you can onboard and manage tenants, run the number pool, monitor
the AI agent and handoffs platform-wide, watch margins (WhatsApp + Claude cost vs MRR),
enforce plans, investigate problems, and never touch the database by hand.

---

## A. Access control & security (do this first)

1. Add `is_super_admin` (bool, default false) to `users`. Super-admins have **no
   `company_id`** (they sit above tenancy). Seed one initial super-admin via a secure
   console command (`php artisan wakeel:make-super-admin`), never hard-coded.
2. Gate the entire Filament panel to `is_super_admin` only — implement
   `FilamentUser::canAccessPanel()` so any normal owner/sales_rep is rejected from
   `/admin`. A failed access attempt is logged.
3. **Tenant-scope bypass:** super-admin queries must intentionally **ignore** the
   `BelongsToCompany` global scope (they need to see ALL companies). Provide a clean,
   explicit mechanism (a panel-wide `withoutGlobalScopes()` context or a dedicated
   super-admin Eloquent context) — and make sure this bypass is impossible from the
   tenant dashboard.
4. Optional roles within admin (e.g. `support` vs `owner` super-admin) via Filament
   policies — at minimum protect destructive actions (suspend, delete, refund) behind an
   extra confirmation + permission.
5. Enforce 2FA for super-admins (Filament's auth or a package). Log every admin login.

---

## B. Resources (full CRUD + relation managers)

### 1. Companies (tenants) — the centerpiece
- Table: name, plan badge, active/suspended state, assigned WhatsApp number, default
  locale, conversations this cycle vs quota (with a usage bar), MRR contribution,
  created date. Sortable/filterable by plan, state, locale, over-quota.
- Filters: plan, active/suspended, over-quota, trialing, churned.
- View page with tabs / relation managers:
  - **Overview:** key stats for this tenant (leads, conversations, hot leads, viewings,
    avg response time, last activity).
  - **Users** (relation manager): list/invite/disable the company's dashboard users;
    reset password; change role.
  - **Units:** read-only count + quick list.
  - **Conversations & handoffs:** recent threads, escalation history.
  - **Subscription & invoices:** current plan, Stripe status, invoice history, next
    renewal.
  - **Usage:** conversation count, message count, token usage, estimated cost this cycle
    and trend.
- **Actions:** suspend / reactivate (toggles `is_active`, with reason logged),
  change/override plan, grant bonus quota / trial extension, **impersonate** (log in as
  that company's owner to debug — heavily audited, time-boxed, with a clear "you are
  impersonating" banner and easy exit), soft-delete (with confirmation + data-retention
  note).

### 2. WhatsApp numbers / channels — promote the pool to a real table
- Create a `whatsapp_channels` table (number, `dialog360_channel_id`, status:
  available/assigned/suspended/retired, `assigned_company_id` nullable, label, added_at).
  Migrate the prompt-04 config stub into this table; update
  `assignNumberFromPool()` to draw from it.
- Filament resource: list all numbers with status + which company holds each; add a new
  number, assign/unassign to a company, suspend/retire, and re-register its webhook
  from the panel. Show health (last inbound seen, webhook OK?).
- Guard: can't assign an already-assigned number; releasing a number from a company
  warns about live conversations.

### 3. Subscriptions / billing oversight
- List active/trialing/past_due/canceled subscriptions (read from Cashier), MRR, churn
  this month. Drill into a subscription → invoices, payment status, manual actions where
  Stripe allows (e.g. mark, link to Stripe dashboard). Surface dunning/past-due tenants
  prominently.

### 4. Conversations & handoffs (platform-wide monitor)
- Global, read-only view of conversations across all tenants (respect privacy — see §F):
  filter by company, mode (bot/pending_handoff/human), date. Spot stuck handoffs
  (waiting too long), companies with high escalation rates, or bot errors.
- Handoffs board: global queue/aging, resolution times, per-company escalation rate.

### 5. Leads (aggregate, read-only)
- Cross-tenant lead volume and tier distribution for analytics; not for editing
  tenant leads (those belong to the tenant). Mainly for platform insight.

### 6. Contact messages (from the marketing site, prompt 19)
- Inbox of `contact_messages`: read, mark handled, assign, reply-via-email action.

### 7. Admin users
- Manage super-admins themselves (invite, disable, 2FA status) — protected.

### 8. Platform settings
- A settings resource/page for global config: default plan limits, Claude model +
  per-tenant token budget caps, WhatsApp cost assumptions (for margin math), feature
  flags, maintenance mode toggle, default locale. Editable without a deploy
  (persisted settings).

---

## C. Dashboard (the admin home) — widgets

Build a Filament dashboard with operator KPIs:
- **Stat cards:** total companies, active subscriptions, MRR (estimate), conversations
  today (platform-wide), messages sent today, hot leads today, numbers available vs
  assigned.
- **Revenue vs cost:** MRR vs estimated platform cost (WhatsApp fees + Claude tokens)
  → gross margin %. A trend chart over time.
- **Growth charts:** new signups, churn, conversations per day (last 30/90 days).
- **Health strip:** failed jobs count (link to Horizon), webhook errors, past-due
  subscriptions, stuck handoffs, over-quota tenants — each a clickable alert.
- **Top tenants:** by usage and by revenue.
- All widgets time-rangeable; numbers rounded; queries efficient (aggregate in SQL,
  cache heavy widgets briefly).

---

## D. System health & operations

1. **Queue/jobs:** link prominently to **Laravel Horizon**; surface failed-job count and
   a quick "retry failed" affordance where safe.
2. **Cost & token tracking:** a per-company token-usage log (from the agent runner) feeds
   a cost view; alert when a tenant exceeds its configured token budget (master-context
   §10 / prompt 17 cost controls).
3. **Error visibility:** recent WhatsApp send failures, Prism/agent errors, webhook
   rejections — filterable, with enough context to act (no secrets/PII leaked).
4. **Maintenance mode** toggle and broadcast a notice banner to tenants if needed.

---

## E. Audit log (critical for an operator panel)

- Record every sensitive admin action: suspend/reactivate, plan change, quota grant,
  number assign/release, impersonation start/stop, refunds, settings changes, super-admin
  management. Store actor, target, before/after, timestamp, IP.
- A read-only, filterable Audit Log resource. This is non-negotiable for trust and
  debugging.

---

## F. Privacy guardrails (important)

- Conversation content is tenant customer data. In the admin monitor, **default to
  metadata** (counts, modes, timing, summaries) and gate full message-content viewing
  behind an explicit, audited "view conversation" permission with a logged reason.
- Redact/avoid exposing customer phone numbers in bulk lists; show partially masked,
  full only on an audited detail view.
- Impersonation is logged, time-boxed, and clearly indicated; never silent.

---

## G. Polish & UX

- Global search across companies, numbers, users, contact messages.
- Bulk actions where sensible (export, suspend with confirmation).
- CSV/Excel **exports** for companies, usage, and revenue (for finance).
- Bilingual admin (reuse prompt 18 `admin.php` lang files; `ar/en` switcher; direction
  follows locale).
- Notifications: in-panel alerts for past-due subs, stuck handoffs, over-quota tenants,
  new contact messages.
- Consistent brand theming of the panel (Wakeel colors/logo).

---

**Tests:**
- Only `is_super_admin` reaches `/admin`; an owner and a sales_rep are both rejected
  (and the attempt is logged).
- Super-admin queries see all tenants (global scope correctly bypassed) while the tenant
  dashboard still cannot.
- Number pool: assign from `whatsapp_channels`, prevent double-assign, release warns;
  `assignNumberFromPool()` now uses the table.
- Suspend toggles `is_active` and writes an audit entry; impersonation starts/stops and
  is audited.
- Plan/quota override changes enforcement (e.g. raising Starter's unit limit).
- Margin widget math: MRR, estimated cost, and gross-margin % compute correctly from
  seeded usage.
- Privacy: bulk lists mask phone numbers; full conversation view requires permission and
  logs a reason.
- Audit log captures each sensitive action with actor/target/before-after/IP.

**Done when:** you can run the entire platform from `/admin` — onboard, assign numbers,
monitor agent + handoffs, watch margins, enforce plans, investigate issues, and review a
complete audit trail — with strict access control, privacy guardrails, and a bilingual,
branded UI.
