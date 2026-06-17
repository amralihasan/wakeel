# PROMPTS 06 → 10 — The AI Agent Core

> Re-paste `00-MASTER-CONTEXT.md` before each prompt. These five prompts turn the
> skeleton into a real thinking agent. This is the heart of the product.

---

## PROMPT 06 — Dynamic system prompt builder

**Goal:** Build the agent's "brain instructions" fresh on every request from the
company's bot settings, so each tenant gets a bot with its own name, tone, and rules —
without any code change.

**Do this:**

1. Create `app/Services/Agent/SystemPromptBuilder.php` with
   `build(Company $company): string`.
2. The prompt (written in **Arabic**, since the bot thinks and replies in Arabic) must
   instruct the model to:
   - Adopt the persona: bot name = `bot_settings.bot_name`, tone =
     `bot_settings.tone` (friendly_egyptian / formal / gulf — give distinct style
     guidance for each).
   - Be a real-estate sales assistant for `{company.name}`. Goal: understand the
     buyer's needs, present matching units, and move serious buyers toward a viewing.
   - **Use the tools** rather than inventing data: never quote a price, availability,
     or unit detail that isn't returned by `search_properties`. If unsure, search.
   - Ask clarifying questions naturally (budget, rooms, location, cash vs installment)
     — one or two at a time, conversational, Egyptian Arabic.
   - Know when to escalate: call `escalate_to_agent` when the company's
     `escalation_rules` are met (lead score ≥ 70, customer explicitly asks for a human,
     or after the configured number of unproductive messages).
   - Never promise anything outside available data; never fabricate legal/financial
     guarantees; be honest if it doesn't know and offer to connect a human.
   - Keep replies short and WhatsApp-appropriate (no walls of text).
3. Inject **dynamic context**: working hours (and what to say off-hours if not 24/7),
   today's date (Africa/Cairo), and a compact summary of the lead so far (name, budget,
   interested unit) pulled from the session/lead.
4. Provide a fallback default for any missing setting.
5. Keep it composable: small private methods per section (`personaSection`,
   `toolPolicySection`, `escalationSection`, `contextSection`) concatenated.

**Tests:** building for a company with `tone=formal` vs `friendly_egyptian` yields
different persona text; escalation thresholds from settings appear in the prompt;
missing settings fall back to defaults without error.

**Done when:** `SystemPromptBuilder::build($company)` returns a coherent Arabic prompt
reflecting that company's settings.

---

## PROMPT 07 — The six agent tools

**Goal:** Implement all six Prism `Tool` classes (master-context §4), each backed by a
tenant-aware service. Tools are the agent's only hands.

**Do this:** for each tool create a Prism Tool class in `app/Agent/Tools/` plus its
backing logic. Every tool is constructed with the `company_id` (and `lead_id` /
`conversation` where needed) so it stays tenant-scoped inside a queued job with no auth
session.

1. **`SearchPropertiesTool`** (`search_properties`)
   - Params: `budget_max` (int, required), `rooms` (int, optional), `location`
     (string, optional), `type` (string, optional).
   - Query `units` for the company, `status = available`, price ≤ budget, optional
     filters, order by best fit, **limit 3**. Return compact JSON (id, title, rooms,
     area, price, location, down_payment, installment_years, has_media). Return a clear
     "no matches" message if empty so the model can respond gracefully.
2. **`SendUnitMediaTool`** (`send_unit_media`)
   - Params: `unit_id` (int), `media_type` (enum images|pdf|floorplan|video).
   - Verify the unit belongs to the company. Resolve media rows → dispatch
     `SendWhatsAppMedia` jobs to the customer. Return a confirmation string (e.g.
     "تم إرسال 4 صور للوحدة A3"). Do NOT block on the send.
3. **`CalculateInstallmentTool`** (`calculate_installment`)
   - Params: `price`, `down_payment`, `years`. Pure math: monthly = (price −
     down_payment) / (years × 12). Round sensibly. Return a formatted EGP string and
     the raw number.
4. **`BookVisitTool`** (`book_visit`)
   - Params: `unit_id`, `date` (YYYY-MM-DD), `time` (HH:MM).
   - Validate the slot (basic: future, within working hours, not double-booked).
     Create a `visit` (status pending), link lead+unit, fire `VisitBooked` event.
     Return confirmation. (+30 to lead score handled by scoring engine via the event.)
5. **`QualifyLeadTool`** (`qualify_lead`)
   - Params: `signals` (object of booleans/values matching master-context §6).
   - Delegate to the scoring engine (prompt 09), persist `score`+`tier` on the lead,
     return the score + tier so the model knows whether to escalate.
6. **`EscalateToAgentTool`** (`escalate_to_agent`)
   - Params: `reason` (string), `summary` (string — the model's one-line summary).
   - Set Redis `mode = pending_handoff`, create a `handoff` (status waiting) storing the
     reason and `ai_summary`, fire `ConversationEscalated` event (drives the live panel
     + Reverb in prompt 10). Return a confirmation the model can relay
     ("هيتواصل معك أحد مستشارينا حالاً").

General rules: each tool has a precise Arabic `description()` (the model reads these to
decide when to call), a typed parameters `Schema`, and a `handle()` that is defensive
(validate ownership, never throw raw to the model — return a usable error string).

**Tests:** unit-test each tool's `handle()` with a faked company/units; assert tenant
scoping (a unit from another company is invisible / rejected); assert events fire.

**Done when:** all six tools are individually tested and green.

---

## PROMPT 08 — The agent runner

**Goal:** Replace the prompt-05 stub with the real Prism agent loop. This is where it
all comes together.

**Do this:**

1. Create `app/Services/Agent/AgentRunner.php` with
   `handle(Company $company, string $customerPhone, string $incomingText): void`.
2. Inside:
   - Load `ConversationSession` (history + mode + lead).
   - Build the system prompt via `SystemPromptBuilder`.
   - Assemble the Prism message history from the session (last N turns) + the new user
     message.
   - Instantiate the six tools bound to this company / lead / conversation.
   - Call:
     ```
     Prism::text()
       ->using('anthropic', 'claude-haiku-4-5')
       ->withSystemPrompt($systemPrompt)
       ->withMessages($history)
       ->withTools($tools)
       ->withMaxSteps(5)
       ->generate();
     ```
   - Take the final assistant text → dispatch `SendWhatsAppText` to the customer.
   - Persist: the assistant message to `messages`, push both turns into Redis history
     (trimmed), update `last_message_at`, `touch()` the session.
3. **Re-check mode after generation:** if a tool set `mode = pending_handoff` mid-run,
   still send the model's final "transferring you" text but ensure no further bot
   replies happen (the gate in the job handles future messages).
4. Robustness: wrap the Prism call in try/catch. On failure, send a graceful Arabic
   fallback ("معلش حصل خطأ بسيط، ممكن تعيد رسالتك؟") and log the exception. Add a
   per-conversation lock so two inbound messages don't run the agent concurrently.
5. Wire `ProcessInboundMessageJob` to call `AgentRunner::handle(...)` when `mode == bot`.

**Tests (with Prism faked):**
- A scripted Prism response that calls `search_properties` then returns text → assert
  the tool ran, the outbound text job dispatched, and history persisted.
- A response that calls `escalate_to_agent` → assert mode flips to `pending_handoff`
  and the handoff record exists.
- Concurrency lock prevents double-run.

**Done when:** the faked end-to-end agent test passes: inbound → reason → tool →
reply → persisted.

---

## PROMPT 09 — Lead scoring engine

**Goal:** A single authoritative scoring service implementing master-context §6, fed by
events and the `qualify_lead` tool.

**Do this:**

1. `app/Services/Leads/LeadScoringService.php`:
   - `applySignals(Lead $lead, array $signals): Lead` — add points per the §6 table
     (idempotent per signal: don't double-count "booked a viewing" twice — track which
     signals already applied, e.g. a `scored_signals` JSON on the lead).
   - `tierFor(int $score): LeadTier` — hot ≥70, warm 40–69, cold <40.
   - Persist score + tier; if it crosses into `hot`, fire `LeadBecameHot`.
2. Event listeners:
   - `VisitBooked` → apply +30 (booked) signal.
   - A turn-level analyzer (optional, cheap): after each agent turn, infer light signals
     (asked about price, asked about installment) from tool calls that happened — feed
     them in. Keep it deterministic from tool usage, NOT a second LLM call.
   - `LeadBecameHot` → if company rules say "escalate at 70", trigger the same path as
     `escalate_to_agent` (so a lead can auto-escalate even if the model didn't call the
     tool).
3. Expose the current score/tier on the lead for the dashboard and the system-prompt
   context summary.

**Tests:** signals accumulate correctly and idempotently; tier thresholds exact at 39/
40 and 69/70 boundaries; crossing 70 fires `LeadBecameHot` once.

**Done when:** scoring is deterministic, idempotent, and event-driven.

---

## PROMPT 10 — Human handoff & live chat panel

**Goal:** The full escalation experience: bot steps aside, the chat enters a queue,
a rep takes it over and chats with the customer from inside the dashboard, then
optionally returns control to the bot. Real-time throughout.

**Do this:**

1. **Events + broadcasting (Reverb):**
   - `ConversationEscalated` (from `escalate_to_agent`) → broadcast on a private
     company channel `company.{id}.handoffs` → dashboard shows a new queue item with the
     `ai_summary`, customer phone, and wait timer.
   - `InboundMessageReceived` while mode ≠ bot → broadcast to the open chat so the rep
     sees customer messages live.
2. **Agent Panel (Livewire v3)** at `/dashboard/conversations`:
   - Left: queue of `handoffs` (status waiting) + active chats, color-coded by wait
     time, newest/most-urgent first, live via Echo.
   - Right: selected conversation thread (full history incl. the bot turns), an
     `ai_summary` header, and a compose box.
   - **"استلام" (Take over)** button → sets Redis `mode = human`, sets
     `handoff.status = active`, `agent_id`, `claimed_at`; assigns
     `conversation.assigned_rep_id`. From now the bot is silent (the job gate enforces).
   - Compose + send → dispatch `SendWhatsAppText` from the rep; store as a `message`
     with sender `rep`. The customer can't tell it's not the bot.
   - **"إنهاء وإعادة للبوت" (Resolve & return)** → capture `resolution_notes`, set
     `handoff.status = resolved` + `resolved_at`, Redis `mode = bot`; optionally have
     the bot send a closing "أقدر أساعدك في حاجة تانية؟".
3. **Gate enforcement:** confirm `ProcessInboundMessageJob` already suppresses the bot
   when mode is `pending_handoff`/`human` (from prompt 05) — add the live broadcast of
   those inbound messages to the panel here.
4. Permissions: only `sales_rep` and `owner` can claim/handle; a chat claimed by one rep
   shows as "مع {rep}" to others.

**Tests:** escalation creates a waiting handoff + broadcasts; "take over" flips mode to
human and silences the bot (prove with a follow-up inbound that gets no auto-reply);
rep message is sent + stored as `rep`; resolve returns mode to bot.

**Done when:** a full escalate → claim → rep replies → resolve cycle works and the bot
stays silent throughout the human phase.
