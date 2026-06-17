# PROMPTS 01 → 05 — Foundation

> Re-paste `00-MASTER-CONTEXT.md` at the top of the context before running each prompt.
> Run in order. Do not skip.

---

## PROMPT 01 — Project scaffold & packages

**Goal:** A fresh, runnable Laravel 12 app named Wakeel with every locked package
installed and configured per the master context. Nothing functional yet — just the
skeleton everything else builds on.

**Do this:**

1. Create a fresh Laravel 12 app (PHP 8.3+). App name `Wakeel`.
2. Install and register these packages:
   - `prism-php/prism` (Laravel AI SDK) — publish its config; set default provider to
     `anthropic` and model to `claude-haiku-4-5`.
   - `livewire/livewire` v3.
   - `laravel/horizon` — publish config; configure to use the Redis connection.
   - `laravel/reverb` — install and publish.
   - `filament/filament` v3 — install the panel at `/admin`.
   - `laravel/cashier` (Stripe).
   - Tailwind CSS + Alpine.js via the Vite pipeline; enable RTL.
3. Configure Redis as the driver for `cache`, `queue`, and `session`.
4. Configure the `database` (MySQL) connection.
5. Add every env key from master-context §10 to `.env.example` with empty values and a
   one-line comment each.
6. Create an `enums/` folder under `app/` and add empty backed enums (string-backed):
   `ConversationMode` (bot, pending_handoff, human), `LeadTier` (hot, warm, cold),
   `UserRole` (owner, sales_rep), `MessageDirection` (inbound, outbound),
   `MessageSender` (customer, bot, rep), `VisitStatus` (pending, confirmed, completed,
   cancelled, no_show), `HandoffStatus` (waiting, active, resolved), `UnitStatus`
   (available, reserved, sold).
7. Set app locale handling so the dashboard renders RTL Arabic and `Africa/Cairo`
   timezone is the display default while storage stays UTC.
8. Install Pest. Add one smoke test that boots the app and hits `/` → 200.

**Done when:** `composer install`, `npm install && npm run build`, `php artisan migrate`
(default tables), `php artisan horizon`, and `vendor/bin/pest` all run clean.

---

## PROMPT 02 — Multitenancy & authentication

**Goal:** The tenant model and auth. A real-estate company can register, gets its own
isolated space, and its dashboard users sign in with roles.

**Do this:**

1. Create the `companies` table & `Company` model. Columns: `id`, `name`, `slug`,
   `email`, `phone`, `plan` (string, default `starter`), `whatsapp_number` (nullable),
   `dialog360_channel_id` (nullable), `bot_settings` (json, nullable), `is_active`
   (bool), timestamps.
2. Add `company_id` (FK, nullable for super-admin) to the `users` table. Add `role`
   column using the `UserRole` enum. Update the `User` model: `belongsTo(Company)`,
   helper `isOwner()`, `isSalesRep()`.
3. Build a **company registration flow** (Livewire): company name + owner name + email
   + password → creates the `Company` and an `owner` `User` in a DB transaction, logs
   them in, redirects to an onboarding screen (placeholder for now).
4. Implement **tenant scoping**:
   - A `BelongsToCompany` trait that adds a global scope filtering by the
     authenticated user's `company_id`, and auto-fills `company_id` on create.
   - This trait will be applied to every tenant-owned model in prompt 03.
5. Add a `CurrentCompany` helper/singleton resolving the logged-in user's company for
   use in services and jobs (jobs must receive `company_id` explicitly, since they run
   without an auth session).
6. Gates/policies: `owner` can manage everything in the company; `sales_rep` can view
   leads, view/handle conversations, manage visits, but cannot edit units, bot
   settings, billing, or team.
7. Tests: a user from company A cannot read company B's records (assert global scope
   isolation); registration creates exactly one company + one owner.

**Done when:** registration works end-to-end and the isolation test passes.

---

## PROMPT 03 — Database schema & models

**Goal:** The full domain model from master-context §7, with migrations, Eloquent
models, relationships, enums wired, and factories for testing.

**Do this:** create migrations + models (each tenant-owned model uses the
`BelongsToCompany` trait) for:

1. **`units`** — `company_id`, `title`, `description`, `type` (apartment/duplex/
   penthouse/villa…), `rooms` (int), `area` (int, m²), `price` (bigint, EGP),
   `location` (string), `down_payment` (bigint, nullable), `installment_years` (int,
   nullable), `status` (`UnitStatus` enum, default available), `delivery_date`
   (nullable). Relationship: `hasMany(UnitMedia)`.
2. **`unit_media`** — `unit_id`, `type` (image/pdf/floorplan/video), `path` (S3),
   `caption` (nullable), `sort_order`.
3. **`leads`** — `company_id`, `customer_phone` (indexed), `name` (nullable),
   `budget_max` (nullable), `preferred_rooms` (nullable), `preferred_location`
   (nullable), `interested_unit_id` (nullable FK), `score` (int, default 0), `tier`
   (`LeadTier`, nullable), `status` (string: new/qualifying/booked/with_rep/closed),
   `source` (string: facebook/website/qr/other). Unique index on
   (`company_id`,`customer_phone`).
4. **`conversations`** — `company_id`, `customer_phone`, `lead_id` (FK), `mode`
   (`ConversationMode`, default bot), `assigned_rep_id` (nullable FK users),
   `last_message_at`. Unique (`company_id`,`customer_phone`).
5. **`messages`** — `company_id`, `conversation_id` (FK), `direction`
   (`MessageDirection`), `sender` (`MessageSender`), `body` (text, nullable),
   `media_url` (nullable), `media_type` (nullable), `wa_message_id` (nullable, for
   dedupe), `created_at` indexed.
6. **`visits`** — `company_id`, `lead_id`, `unit_id`, `scheduled_at` (datetime),
   `status` (`VisitStatus`), `assigned_rep_id` (nullable), `notes` (nullable).
7. **`handoffs`** — `company_id`, `conversation_id`, `lead_id`, `reason` (string),
   `ai_summary` (text), `status` (`HandoffStatus`, default waiting), `agent_id`
   (nullable), `claimed_at` (nullable), `resolved_at` (nullable), `resolution_notes`
   (nullable).

Wire every relationship both directions. Add factories for all models. Add an index
review note: index `customer_phone`, `conversation_id`, `status`, `tier`, `created_at`
where queried.

**Tests:** factory-create one of every model under a company; assert relationships
resolve and tenant scope holds.

**Done when:** `php artisan migrate:fresh` runs clean and all factory/relationship
tests pass.

---

## PROMPT 04 — 360dialog integration service

**Goal:** A clean service wrapping the 360dialog WhatsApp Cloud API for everything we
send OUT, plus number provisioning. This is the only place that talks to 360dialog.

**Do this:**

1. Create `app/Services/WhatsApp/WhatsAppClient.php` (interface
   `WhatsAppClientContract` + `Dialog360Client` implementation, bound in a service
   provider). Methods:
   - `sendText(string $channelId, string $to, string $body): string` (returns wa msg id)
   - `sendImage($channelId, $to, string $url, ?string $caption)`
   - `sendDocument($channelId, $to, string $url, ?string $filename, ?string $caption)`
   - `sendInteractiveButtons($channelId, $to, string $body, array $buttons)`
   - `sendInteractiveList($channelId, $to, string $body, array $sections)`
   - `sendLocation($channelId, $to, float $lat, float $lng, ?string $name)`
2. Read base URL + API key from config (`config/services.php` → `dialog360`). Use
   Laravel's HTTP client with retry + timeout. Throw a typed
   `WhatsAppException` on non-2xx and log the payload (without secrets).
3. Provisioning helper `assignNumberFromPool(Company $company): void` — for Model B:
   pick an available channel from our configured pool, store
   `dialog360_channel_id` + `whatsapp_number` on the company, and register/confirm the
   webhook for that channel. (Stub the pool as a config array of available channels for
   now; mark a TODO to make it a `whatsapp_channels` table later.)
4. **Never send inline from a request.** Provide queued jobs `SendWhatsAppText`,
   `SendWhatsAppMedia` that call the client. Outbound from tools/agent always goes
   through these jobs.
5. Fake/mock the HTTP layer in tests; assert correct endpoint, headers, and body shape
   for each method.

**Done when:** unit tests pass against a faked HTTP client for every send method, and
`assignNumberFromPool` populates the company correctly.

---

## PROMPT 05 — WhatsApp webhook & inbound pipeline

**Goal:** Receive inbound WhatsApp messages, respond to Meta instantly, and push the
real work onto the queue. Establish the Redis session and the `mode` gate — the heart
of the whole system.

**Do this:**

1. **Verify route** `GET /webhook/whatsapp` — handles the webhook verification
   handshake (echo `hub.challenge` when `hub.verify_token` matches
   `WHATSAPP_WEBHOOK_VERIFY_TOKEN`).
2. **Inbound route** `POST /webhook/whatsapp` — controller must:
   - Validate signature/source where 360dialog provides it.
   - Parse the payload → extract `channelId`, `from` (customer phone), message type,
     text/media, and `wa_message_id`.
   - Resolve the `Company` by `dialog360_channel_id`.
   - **Dedupe** on `wa_message_id` (ignore repeats).
   - Persist the inbound `message` + ensure a `conversation` and `lead` exist.
   - **Dispatch `ProcessInboundMessageJob`** with `company_id`, `customer_phone`,
     message data — then **return HTTP 200 immediately** (no AI work in the request).
3. **Redis session** — helper `ConversationSession` keyed
   `session:{company_id}:{phone}` (hash): `mode`, `lead_id`, `history` (JSON, trimmed
   to last 12 turns), `agent_id?`, `updated_at`. Methods: `get`, `getMode`, `setMode`,
   `pushTurn`, `history`, `touch`. Default mode `bot`.
4. **`ProcessInboundMessageJob`** (skeleton for now — the agent goes in prompt 08):
   - Load session.
   - **Gate:** if `mode` is `human` or `pending_handoff` → store the message, broadcast
     it to the live panel (placeholder event), and **return without replying**.
   - If `mode == bot` → (placeholder) call a `AgentRunner` stub that, for now, just
     echoes a fixed Arabic greeting via `SendWhatsAppText`. Prompt 08 replaces the stub
     with the real Prism agent.
5. Make the job idempotent and rate-limited per phone (avoid double-processing).

**Tests:** verification handshake returns the challenge; a posted inbound payload
creates message/conversation/lead, dispatches the job, returns 200; the `human`-mode
gate suppresses any auto-reply.

**Done when:** a simulated inbound message flows webhook → job → outbound stub reply,
and the mode gate is proven by test.
