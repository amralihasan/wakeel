# 00 — MASTER CONTEXT — Wakeel (وكيل) Real Estate WhatsApp AI Agent

> **READ THIS FILE FIRST IN EVERY SESSION.**
> This is the single source of truth for the entire project. Every numbered prompt
> (`01` … `N`) builds on the decisions locked here. If a prompt ever contradicts this
> file, this file wins. Paste this file at the top of the Cursor context before
> running any build prompt.

---

## 1. What we are building

A multi-tenant **SaaS platform** that gives real-estate developers an **AI Agent**
(not a scripted chatbot) that talks to their leads over **WhatsApp** — in Egyptian
Arabic — qualifies them, sends unit media, books viewings, scores them, and hands the
hot ones to a human sales rep through an in-platform live-chat panel.

**Two distinct user-facing surfaces:**

1. **The end customer** — a property buyer. Never sees the platform. Only chats with a
   normal WhatsApp number. Does not know an AI is replying.
2. **The client company** — a real-estate developer. Logs into a web **Dashboard** to
   manage units, configure the bot, watch conversations, see leads, and take over chats.

**We (the platform owner)** operate the infrastructure: the WhatsApp Business API
account, a pool of phone numbers, the servers, and the Claude API subscription.

---

## 2. Non-negotiable decisions (LOCKED)

| Decision | Choice | Why |
|---|---|---|
| Backend framework | **Laravel 12** (PHP 8.3+) | Owner's primary stack |
| AI layer | **Laravel Prism** (official AI SDK) | Native tool-calling, multi-provider, no Python needed |
| LLM | **Claude Haiku 4.5** (`claude-haiku-4-5`) | Fast, cheap, excellent Arabic |
| Conversation memory (live) | **Redis** | Session state + `mode` flag per phone number |
| Conversation history (durable) | **MySQL** | Full message log, leads, handoffs |
| Queue / async | **Laravel Horizon** on Redis | Process inbound messages off the request cycle |
| Frontend (Dashboard) | **Livewire v3 + Alpine.js + Tailwind CSS** | Owner's stack; real-time without SPA |
| Real-time push | **Laravel Reverb** (+ Echo) | Notify dashboard of handoffs / new leads |
| WhatsApp provider (BSP) | **360dialog** for MVP | Fastest onboarding, number pool |
| Onboarding model | **Model B — platform supplies the number** | 5-min onboarding for MVP |
| Media storage | **Laravel Storage + S3** (or compatible) | Unit images, PDFs, floor plans |
| Admin panel | **Filament v3** at `/admin` | Platform-owner super-admin |
| Multitenancy | **Single DB, `company_id` scoping** | Simpler than DB-per-tenant for MVP |
| Payments | **Stripe** (subscriptions) | Plan billing |

**Do NOT** introduce: Python, LangChain, LangGraph, Node services, or any other LLM
orchestration layer. Prism's `withMaxSteps()` IS the agent loop.

---

## 3. The agent loop (how "thinking" works)

Every inbound customer message is handled by one Prism call inside a queued job:

```
Inbound WhatsApp message
   → Webhook (Laravel route) returns 200 immediately
   → Dispatch ProcessInboundMessageJob (Horizon)
   → Job loads session from Redis (history + mode)
   → IF mode == human  → store message, do NOT reply (a human is handling it)
   → ELSE → Prism::text()
              ->using('anthropic', 'claude-haiku-4-5')
              ->withSystemPrompt(dynamic, per-company)
              ->withMessages(history)
              ->withTools([...the 6 tools below...])
              ->withMaxSteps(5)
              ->generate()
   → Prism auto-executes any tool the model calls, loops, returns final text
   → Send final text (and any queued media) back via 360dialog API
   → Persist everything to MySQL + update Redis history
```

The model decides *on its own* whether to ask a question, search units, send media,
calculate installments, score the lead, or escalate. We never hard-code the branching.

---

## 4. The six tools (agent capabilities)

Each is a Prism `Tool` class backed by a Laravel service. Tools are the ONLY way the
agent touches the database or the outside world.

| Tool | Signature (params) | Returns | Job |
|---|---|---|---|
| `search_properties` | `budget_max:int, rooms:int?, location:string?, type:string?` | JSON list (max 3) | Query `units` scoped to company |
| `send_unit_media` | `unit_id:int, media_type:enum(images\|pdf\|floorplan\|video)` | confirmation | Queue media send via WhatsApp |
| `calculate_installment` | `price:int, down_payment:int, years:int` | monthly amount | Pure calculation |
| `book_visit` | `unit_id:int, date:string, time:string` | confirmation + slot | Insert into `visits`, fire event |
| `qualify_lead` | `signals:object` | score + tier(hot/warm/cold) | Compute + persist on `leads` |
| `escalate_to_agent` | `reason:string, summary:string` | confirmation | Set Redis `mode=pending_handoff`, create `handoff`, fire event |

**Important:** tools return strings/JSON back to the model so it can keep reasoning.
Media and outbound WhatsApp sends are themselves dispatched as jobs (never inline).

---

## 5. Session state machine (Redis)

Key: `session:{company_id}:{customer_phone}` (a Redis hash).

Fields: `mode`, `agent_id?`, `lead_id`, `history` (JSON, trimmed to last N turns),
`updated_at`.

```
mode = bot              → AI Agent replies to every message
mode = pending_handoff  → escalation requested; AI is silent; in queue, no agent yet
mode = human            → a sales rep owns the chat; AI is silent; rep replies from panel
```

Transitions:
- `bot → pending_handoff` : tool `escalate_to_agent` fires (score ≥ threshold, or
  customer asks for a human, or company rule met).
- `pending_handoff → human` : a rep clicks "استلام" (take over) in the Agent Panel.
- `human → bot` : rep clicks "إنهاء وإعادة للبوت" (resolve & return to bot).

The webhook MUST check `mode` BEFORE invoking Prism. If `human` or `pending_handoff`,
store the message and stop.

---

## 6. Lead scoring (real-estate logic)

Computed by `qualify_lead` and/or after each turn. Signal → points:

| Signal | Points |
|---|---|
| Stated a clear budget | +30 |
| Asked about a specific unit's price | +25 |
| Booked a viewing | +30 |
| Asked about installment / financing | +15 |
| Asked for floor plan / brochure | +10 |
| General inquiry only | +5 |

Tiers: `hot ≥ 70` (escalate immediately) · `warm 40–69` (bot keeps nurturing) ·
`cold < 40` (automated follow-up sequence).

---

## 7. Data model (high level — full schema in prompt 03)

- `companies` — tenant. Has `whatsapp_number`, `360dialog_channel_id`, plan, bot config.
- `users` — dashboard users; belong to a company; role: `owner | sales_rep`.
- `units` — properties; belong to company; price, rooms, area, location, status, media.
- `unit_media` — images/PDF/floorplan/video rows for a unit (S3 paths).
- `leads` — one per (company, customer_phone); name, budget, interested_unit, score, tier, status.
- `conversations` — one per (company, customer_phone); links to lead; current mode.
- `messages` — every message; direction (inbound/outbound), sender (customer/bot/rep), body, media.
- `visits` — booked viewings; unit, lead, datetime, status, assigned_rep.
- `handoffs` — escalation records; conversation, reason, ai_summary, status, agent_id, timestamps.
- `subscriptions` / Stripe tables — billing.

**Every tenant-owned table carries `company_id` and is always scoped by a global scope
or explicit `where('company_id', ...)`.** Never trust a query that isn't tenant-scoped.

---

## 8. Bot configuration (per company, drives the system prompt)

Stored on `companies` (or a `bot_settings` JSON column). The dashboard edits these:
- `bot_name` (e.g. "نور المساعد العقاري")
- `tone` (enum: `friendly_egyptian | formal | gulf`)
- `working_hours` (`24_7` or a schedule)
- `escalation_rules` (booleans: score≥70, customer asks for human, after N msgs)
- `language` (default `ar-EG`)

The system prompt is **built dynamically** from these settings at request time
(prompt 06 covers the builder).

---

## 9. Naming, conventions, language

- App / brand: **Wakeel (وكيل)**. Namespace `App\`.
- Code, comments, identifiers: **English**.
- All customer-facing text and all bot output: **Arabic (Egyptian)**.
- Dashboard UI: **Arabic, RTL** (`dir="rtl"`), Tailwind RTL.
- Money: stored as integers (piastres/cents) where practical; display formatted EGP.
- Times: store UTC, display Africa/Cairo.
- Follow PSR-12. Use typed properties, enums (PHP 8.1 backed enums) for `mode`, `tier`,
  `role`, `status`, `direction`.
- Tests: Pest. Each feature prompt ends by adding tests.

---

## 10. Environment variables (.env keys to expect)

```
APP_NAME=Wakeel
ANTHROPIC_API_KEY=
PRISM_DEFAULT_PROVIDER=anthropic
WHATSAPP_BSP=360dialog
DIALOG360_API_KEY=
DIALOG360_BASE_URL=https://waba-v2.360dialog.io
WHATSAPP_WEBHOOK_VERIFY_TOKEN=
REDIS_HOST=
REDIS_PORT=6379
DB_CONNECTION=mysql
AWS_BUCKET=          # or S3-compatible
STRIPE_KEY=
STRIPE_SECRET=
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
```

---

## 11. Build order (the prompt series)

Run the numbered prompts **in order**. Each is self-contained but assumes the previous
ones ran. Re-paste THIS master context at the start of each prompt.

| # | Prompt | Produces |
|---|---|---|
| 01 | Project scaffold & packages | Fresh Laravel 12, all packages installed & configured |
| 02 | Multitenancy & auth | Companies, users, roles, registration, tenant scoping |
| 03 | Database schema & models | All migrations, models, enums, factories, relationships |
| 04 | 360dialog integration service | Outbound send (text/media/buttons), channel provisioning |
| 05 | WhatsApp webhook & inbound pipeline | Verify route, webhook, ProcessInboundMessageJob, Redis session |
| 06 | Dynamic system prompt builder | Per-company prompt assembled from bot settings |
| 07 | The six agent tools | All Prism Tool classes + backing services |
| 08 | The agent runner | Wires Prism + tools + history + maxSteps into the job |
| 09 | Lead scoring engine | Scoring service, tiers, persistence, events |
| 10 | Human handoff & live chat panel | Handoff flow, mode transitions, Livewire panel, Reverb |
| 11 | Units management (dashboard) | CRUD + media upload to S3, Livewire |
| 12 | Bot settings & onboarding wizard | Settings screen, number assignment, 5-min onboarding |
| 13 | Dashboard overview & analytics | Metrics, leads table, visits, charts |
| 14 | Automated follow-up sequences | Scheduled nurture for warm/cold/no-show |
| 15 | Billing (Stripe) & plan limits | Subscriptions, usage metering, quota enforcement |
| 16 | Filament super-admin | Platform-owner panel: tenants, numbers, usage |
| 17 | Hardening & deploy | Security, rate limits, queue config, deployment notes |

---

## 12. Definition of done (applies to every prompt)

- Code runs with `php artisan ...` and `composer` with no errors.
- Everything tenant-scoped where applicable.
- New env keys documented in `.env.example`.
- Pest tests added and passing for the new behavior.
- No secrets committed. No Python. No extra orchestration libs.
- Arabic for all end-user/bot strings; English for code.
