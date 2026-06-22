# PROMPT 20 — Multi-model AI (Claude + GPT) with admin switching

> Re-paste `00-MASTER-CONTEXT.md` before running this prompt.
> This makes the AI provider/model **configurable and switchable** instead of hard-wired
> to Claude Haiku. It touches the agent core (prompts 06, 08), cost tracking (17), and
> the admin (expanded 16). Run after the agent works on Claude (after prompt 08) so you
> have a working baseline to switch against.

> **Update the master context:** §2 LLM row → **multi-provider via Prism: Anthropic
> (Claude) + OpenAI (GPT), switchable; default `claude-haiku-4-5`**. §10 → add
> `OPENAI_API_KEY` and model-selection keys. §3 agent loop → the `->using(...)` call is
> now resolved dynamically per request, not hard-coded.

---

**Goal:** Run the exact same AI agent — same system prompt, same six tools, same
handoff/scoring logic — on **either Claude or GPT**, chosen by configuration. The
platform super-admin sets which models are available and the platform default; each
company can optionally pick from the allowed models. Switching is safe, observable, and
never breaks tool-calling or Arabic quality.

---

## A. Model registry (single source of truth)

1. Create `config/ai_models.php` — the catalog of supported models. Each entry:
   `key` (e.g. `claude-haiku-4-5`, `gpt-4o`, `gpt-4o-mini`), `provider`
   (`anthropic|openai`), `label` (for admin UI), `supports_tools` (bool — must be true to
   be selectable as the agent model), `input_cost_per_mtok`, `output_cost_per_mtok` (for
   margin math), `enabled` (bool). Seed with at least:
   - `claude-haiku-4-5` (anthropic) — current default
   - `claude-sonnet-4-6` (anthropic) — higher-quality option
   - `gpt-4o` (openai)
   - `gpt-4o-mini` (openai) — cheap option
2. An `AiModelRegistry` service: `all()`, `enabled()`, `find($key)`, `default()`,
   `isSelectable($key)` (enabled AND supports_tools). Nothing else hard-codes a model
   string.
3. Env: add `OPENAI_API_KEY=`; keep `ANTHROPIC_API_KEY=`. Ensure Prism's config exposes
   both the `anthropic` and `openai` providers with their keys.

---

## B. Where the selection lives (resolution order)

Mirror the bilingual two-level pattern:
1. **Platform default** — a global setting (in the prompt-16 platform settings):
   `default_ai_model` (a registry key). Falls back to `config('ai_models.default')` =
   `claude-haiku-4-5`.
2. **Per-company override (optional)** — add `ai_model` (nullable string) to `companies`
   (or `bot_settings`). When set and selectable, it wins for that tenant; when null →
   platform default.
3. **`AiModelResolver::for(Company $company): ModelChoice`** returns the resolved
   `{provider, model_key}` applying: company override (if valid+selectable) → platform
   default → config default. If a previously-selected model becomes disabled, resolve to
   the default and log a warning (never crash a live conversation).

---

## C. Wire it into the agent (the core change)

1. In **`AgentRunner`** (prompt 08), replace the hard-coded
   `->using('anthropic','claude-haiku-4-5')` with the resolved choice:
   ```
   $choice = AiModelResolver::for($company);
   Prism::text()
     ->using($choice->provider, $choice->model)
     ->withSystemPrompt($systemPrompt)
     ->withMessages($history)
     ->withTools($tools)
     ->withMaxSteps(5)
     ->generate();
   ```
2. **Tool compatibility:** the six tools (prompt 07) are defined once via Prism's tool
   abstraction and must work identically on both providers. Verify Prism maps our tool
   schemas to OpenAI function-calling and Anthropic tool-use correctly. Add a guard:
   only models with `supports_tools=true` are selectable for the agent.
3. **System prompt portability:** the Arabic system prompt (prompt 06) stays the same;
   add a tiny per-provider adapter ONLY if needed (e.g. minor formatting nuance) —
   prefer one prompt that works on both. Keep persona/tone identical across models.
4. **Per-conversation consistency:** resolve the model once at the start of a run and use
   it for the whole multi-step loop (don't switch mid-loop).

---

## D. Reliability & fallback

1. Wrap the Prism call so a provider/model failure (auth, rate limit, outage) is caught.
   Optional **fallback model** (config `ai_models.fallback`, default `gpt-4o-mini` or
   `claude-haiku-4-5` — the other provider) so an Anthropic outage can fail over to
   OpenAI (or vice-versa) for that message, logged as a fallback event. Make fallback
   toggleable per platform setting.
2. Keep the graceful Arabic error reply (prompt 08) if both primary and fallback fail.
3. Respect `withMaxSteps` caps regardless of model; trim history the same way.

---

## E. Cost & usage tracking per model (feeds margin math)

1. Capture token usage from each Prism response (input/output tokens) and record per
   conversation/company **which model ran** and the **estimated cost** using the
   registry's per-MTok pricing. Store on a usage log (extends prompt 17 cost controls).
2. Surface in the admin (prompt 16): cost by model, by provider, and per company — so the
   revenue-vs-cost / margin widget reflects the actual model mix (GPT-4o costs differently
   than Haiku).

---

## F. Admin switching UI (Filament — extends expanded prompt 16)

1. **Platform settings page:** manage the model registry — enable/disable models, set the
   **platform default**, set the **fallback**, toggle fallback on/off. Only models with
   `supports_tools` can be set as default/agent model.
2. **Per-company control (Companies resource):** an `ai_model` selector on the company
   detail (choose from enabled+selectable models or "use platform default"). Audited
   (who changed which tenant's model, before/after) per prompt 16's audit log.
3. **Visibility:** show each company's currently-resolved model and recent model mix +
   cost on its overview.
4. *(Optional, if you want tenants to self-serve)* expose a read-only or limited model
   choice in the company's own bot-settings (prompt 12) — gated by plan if you want it as
   a premium feature. Default: keep model choice admin-only for MVP.

---

## G. Test the SAME behavior on both models

This is the point of the prompt — parity, not just plumbing.

1. A provider-agnostic agent test (faked Prism) asserting the resolver picks the right
   model per the two-level rule (company override > platform default > config).
2. Run the existing agent flow tests (prompt 08: search → reply; escalate → handoff)
   parameterized over BOTH a Claude choice and a GPT choice — both must drive the same
   tool calls and outcomes.
3. A disabled-model safety test: a company pointing at a now-disabled model resolves to
   default without error and logs a warning.
4. Fallback test: primary provider throws → fallback model handles the message → logged.
5. Cost-tracking test: usage log records the correct model + estimated cost per the
   registry pricing.
6. Tool-schema compatibility smoke: each of the six tools is accepted by both providers
   (assert no schema rejection when targeting `openai` vs `anthropic`).

**Done when:** the same agent runs on Claude or GPT purely by configuration; the
super-admin can enable models, set a platform default + fallback, and override per
company (all audited); cost tracking reflects the actual model used; and the full agent
behavior (tools, escalation, scoring, Arabic quality) is verified on both providers.

---

### Note on quality
Claude Haiku and GPT differ in tone and Arabic handling. After wiring, run the same set
of real Egyptian-Arabic conversations through each and compare quality/cost before
choosing a production default — keep `claude-haiku-4-5` as the documented default until a
side-by-side justifies otherwise. The switch exists so you can make that call with data,
not so models change silently.
