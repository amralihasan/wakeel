# PROMPTS 11 → 17 — Dashboard, SaaS & Hardening

> Re-paste `00-MASTER-CONTEXT.md` before each prompt. These complete the product:
> the client-facing dashboard, onboarding, billing, super-admin, and production
> hardening.

---

## PROMPT 11 — Units management (dashboard)

**Goal:** The client company manages its property inventory — the data the agent's
`search_properties` and `send_unit_media` tools read from.

**Do this:**

1. Livewire CRUD at `/dashboard/units` (RTL Arabic, Tailwind):
   - List view: cards or table, status badge (available/reserved/sold), price (EGP
     formatted), rooms, area, location, media count. Filter by status; search by
     title/location.
   - Create/Edit form: all `units` fields, including `down_payment`,
     `installment_years`, `delivery_date`, `status`.
2. **Media upload** to S3:
   - Multiple images, an optional brochure PDF, an optional floor-plan image, an
     optional video URL. Store rows in `unit_media` with `type`, `path`, `caption`,
     `sort_order`. Validate file types/sizes. Generate and store accessible URLs the
     WhatsApp send jobs can use.
   - Drag-to-reorder images (Alpine) updating `sort_order`.
3. Tenant-scope everything (owner only — sales reps read-only per prompt 02 policies).
4. When a unit is set to `reserved`/`sold`, it must drop out of `search_properties`
   results (already handled by the `status=available` filter — add a test confirming).

**Tests:** owner can CRUD units + media; sales_rep is read-only; reserved units are
excluded from search; uploaded media produces usable URLs (faked S3).

**Done when:** a company can fully populate its inventory and the agent can find/send it.

---

## PROMPT 12 — Bot settings & onboarding wizard

**Goal:** The 5-minute onboarding (Model B) and the screen where the company shapes its
bot — both of which feed the dynamic system prompt.

**Do this:**

1. **Onboarding wizard** (Livewire, shown right after registration):
   - Step 1: confirm company details.
   - Step 2: choose plan (placeholder until billing in prompt 15 — allow a trial).
   - Step 3: **auto-provision number** — call
     `WhatsAppClient::assignNumberFromPool($company)`; show the assigned WhatsApp
     number and a "this is your bot's number — put it in your ads & site" explainer with
     a ready-made `wa.me/<number>?text=مهتم` link and a downloadable QR code.
   - Step 4: quick bot setup (name + tone) and "add your first unit" CTA.
   - Mark onboarding complete on the company.
2. **Bot settings screen** at `/dashboard/bot-settings` (owner only):
   - Edit `bot_name`, `tone` (friendly_egyptian/formal/gulf), `working_hours`
     (24/7 toggle or schedule), `escalation_rules` (checkboxes: score≥70, customer asks
     for human, after N messages — N editable), `language`.
   - A **"Test the bot"** box: type a message and see the agent's reply in-dashboard
     (runs `AgentRunner` against a sandbox phone, no real WhatsApp send) so they can tune
     tone before going live.
   - A master **"Bot active / paused"** toggle (when paused, the job stores inbound but
     doesn't reply).
3. Persist settings to `companies.bot_settings` (json). Changing them changes the next
   built system prompt with no deploy.

**Tests:** onboarding provisions a number and completes; settings persist and are
reflected by `SystemPromptBuilder`; pause toggle suppresses replies; the in-dashboard
test box returns a reply without sending WhatsApp.

**Done when:** a brand-new company can go from registration to a live, tuned bot in
minutes.

---

## PROMPT 13 — Dashboard overview & analytics

**Goal:** The home dashboard and reporting from the earlier mockups — the company's
at-a-glance control room.

**Do this:**

1. **Overview** at `/dashboard`:
   - Metric cards: conversations today, hot leads, viewings this week, avg response
     time. With day-over-day deltas.
   - "Latest leads" panel (name, interested unit, tier pill) and "recent activity"
     feed (bookings, new leads, escalations) — live via Echo.
   - A weekly conversations bar chart.
2. **Leads** at `/dashboard/leads`:
   - Sortable/filterable table: name, budget, interested unit, status, score/tier.
     Filter Hot/Warm/Cold. Click → lead detail with full conversation history and
     timeline.
3. **Visits** at `/dashboard/visits`:
   - Upcoming viewings table/calendar; status (pending/confirmed/completed/no_show);
     assign a rep; mark outcomes.
4. **Analytics** at `/dashboard/analytics`:
   - Monthly totals (conversations, qualified leads, viewings, conversion %).
   - Lead sources breakdown (facebook/website/qr) with bars.
   - Per-rep performance (handoffs handled, conversions).
   - Use real aggregate queries, tenant-scoped; round all displayed numbers.
5. Build charts with a light JS lib already in the stack; keep queries efficient
   (eager-load, aggregate in SQL, cache heavy ones briefly).

**Tests:** metrics compute correctly from seeded data; filters work; everything
tenant-scoped; conversion % math is right.

**Done when:** the dashboard reflects real data accurately and updates live.

---

## PROMPT 14 — Automated follow-up sequences

**Goal:** Stop leads going cold. The bot proactively follows up — the §"متابعة تلقائية"
behavior — on a schedule.

**Do this:**

1. Define sequences (configurable defaults):
   - **Silent after chat:** no reply for 24h → follow-up message #1; still nothing at
     72h → message #2 with a different angle (new unit / offer).
   - **Booked but no-show:** visit marked `no_show` → reminder + offer to reschedule.
   - **Cold leads:** after 7 days idle → enter a low-frequency nurture (e.g. monthly
     "new units" note).
2. Implement as scheduled jobs (`php artisan schedule`): a command scans conversations/
   leads for due follow-ups and dispatches them. Each follow-up message is **generated
   by the agent** (so it's personalized to the lead context) or from approved templates
   where WhatsApp's 24-hour session window requires a pre-approved template (IMPORTANT:
   outside the 24h customer-service window, WhatsApp only allows **approved template
   messages** — handle this distinction explicitly).
3. Respect: don't follow up if the lead replied, converted, opted out, or is currently
   `human`/`pending_handoff`. Cap total follow-ups. Record each follow-up as a `message`.
4. Make sequences pausable per company in bot settings.

**Tests:** due-detection picks the right leads at the right boundaries; no follow-up to
active/converted/human conversations; template-vs-freeform path chosen correctly by the
24h window; caps respected.

**Done when:** the scheduler reliably re-engages dormant leads without spamming or
breaking WhatsApp policy.

---

## PROMPT 15 — Billing (Stripe) & plan limits

**Goal:** Turn it into a real SaaS: subscriptions, the three plans, and quota
enforcement so usage maps to the plan (master-context pricing).

**Do this:**

1. Use Laravel Cashier (Stripe). Plans: **Starter** (500 conversations/mo, 1 number,
   10 units, 1 rep), **Growth** (2,000, 1 number, unlimited units, 3 reps),
   **Enterprise** (10,000, 2 numbers, unlimited, full team). Define as config +
   Stripe price IDs.
2. Subscription flow in `/dashboard/billing`: choose plan, checkout, manage/cancel,
   invoices. Webhooks for status changes; activate/deactivate company accordingly.
3. **Usage metering:** count billable conversations per company per cycle (a
   conversation = a customer thread with ≥1 bot/rep reply in the period — define
   precisely and store a monthly counter). Enforce:
   - At quota: warn the owner; soft-block or queue per policy (decide & document — for
     MVP: keep replying but flag overage and prompt upgrade).
   - Enforce seat limits (reps), unit limits, and number limits at the UI + server.
4. Surface usage on the dashboard (X / quota used this cycle).

**Tests:** plan limits enforced (can't add an 11th unit on Starter, 2nd rep, etc.);
usage counter increments per billable conversation and resets each cycle; webhook
toggles `is_active`.

**Done when:** a company can subscribe, is held to its plan, and usage is visible.

---

## PROMPT 16 — Filament super-admin

**Goal:** The platform owner's control panel at `/admin` — managing all tenants,
numbers, and platform-wide health. This is YOUR view, not the client's.

**Do this:**

1. Filament v3 panel at `/admin`, gated to super-admins only (users with no
   `company_id` / an `is_super_admin` flag — add it).
2. Resources:
   - **Companies:** list all tenants, plan, active state, usage this cycle, assigned
     number; impersonate/inspect; suspend/reactivate.
   - **WhatsApp numbers/channels:** manage the pool (promote the prompt-04 config stub
     into a real `whatsapp_channels` table here: number, channel_id, status
     available/assigned, assigned_company_id); add/retire numbers.
   - **Usage & cost:** per-company conversation counts and an estimated cost
     (WhatsApp fees + Claude tokens) vs revenue, so you can watch margins.
   - **Handoffs / health:** global view of escalations, error logs, failed jobs
     (link to Horizon).
3. Widgets: total companies, active subscriptions, MRR estimate, total conversations
   today, messages sent.

**Tests:** super-admin access only (a normal owner is rejected from `/admin`); number
pool assignment from the table works end-to-end; suspend toggles tenant activity.

**Done when:** you can run the whole platform — onboard, monitor, and manage tenants —
from `/admin`.

---

## PROMPT 17 — Hardening & deployment

**Goal:** Make it production-safe and document how to ship it.

**Do this:**

1. **Security:**
   - Verify/validate every webhook (360dialog signature) and reject unsigned.
   - Rate-limit the webhook and per-phone processing; protect against payload floods.
   - Ensure no secrets in logs; redact phone numbers where appropriate; encrypt
     sensitive columns if needed.
   - Re-audit tenant scoping across every model, query, broadcast channel, and route.
     Add a test that fuzzes cross-tenant access on each endpoint.
2. **Reliability:**
   - Horizon supervisor config + retry/backoff for inbound, send, and follow-up jobs.
     Dead-letter handling for repeatedly failing sends.
   - Idempotency on inbound (`wa_message_id`) and on outbound (avoid duplicate sends).
   - Timeouts + graceful fallback around every Prism and 360dialog call.
   - Per-conversation locking to serialize agent runs.
3. **Cost controls:** cap `withMaxSteps`; trim history aggressively; log token usage per
   company; alert on anomalies.
4. **Observability:** structured logging, a `/health` endpoint, queue + Reverb
   monitoring notes.
5. **Deployment doc** (`DEPLOY.md`): server requirements (PHP 8.3, Redis, MySQL, a
   queue worker via Horizon, Reverb process, scheduler cron, S3), env setup,
   360dialog channel + webhook registration steps, Stripe webhook setup, build & migrate
   commands, and a go-live checklist.
6. Full Pest suite green; add a couple of end-to-end happy-path feature tests
   (inbound → reply; escalate → handle) and the cross-tenant security fuzz test.

**Done when:** the suite is green, the security audit passes, and `DEPLOY.md` lets
someone bring the platform up from scratch.

---

## After 17 — you have a complete product

Customer side: WhatsApp AI agent that qualifies, sends media, books, scores, escalates.
Client side: a full multi-tenant dashboard with units, bot config, live handoff,
analytics, follow-ups, and billing. Owner side: a Filament panel running the whole SaaS.
All Laravel. No Python.
